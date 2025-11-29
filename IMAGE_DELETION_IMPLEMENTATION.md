# Image Deletion Implementation

## Overview
When an article is deleted, all associated images are now automatically deleted from Wasabi storage to prevent orphaned files and save storage costs.

## What Gets Deleted

When an article is deleted, the system now automatically removes:

1. **Featured Image** - The main article thumbnail/cover image
2. **Content Images** - All images embedded in the article content (across all language translations)
3. **Media Relationship Images** - Images associated through the `article_media` table
4. **All Image Versions** - For each image, all generated versions are deleted:
   - Original
   - Large (1920px)
   - Medium (800px)
   - Thumbnail (300px)

## Implementation Details

### Files Modified
- `/app/Http/Controllers/API/ContentController.php`

### Key Methods Added

#### 1. `deleteArticleImages(Article $article)`
- Main method that orchestrates image deletion
- Extracts image paths from three sources:
  - Featured image field
  - HTML content (all translations)
  - Media relationship
- Removes duplicates and calls MediaService to delete each image
- Includes error handling to ensure article deletion continues even if image deletion fails

#### 2. `extractPathFromUrl(string $url)`
- Converts full Wasabi URLs to storage paths
- Example: `https://s3.eu-central-2.wasabisys.com/oshongshoy/images/original/abc123.jpg` 
- Returns: `images/original/abc123.jpg`
- Handles multiple URL formats from Wasabi

### Code Flow

```php
destroy($request, $id)
  ↓
Load article with translations and media
  ↓
Check permissions (ownership, status)
  ↓
deleteArticleImages($article) 
  ↓
  • Extract featured_image path
  • Extract images from content HTML (all translations)
  • Extract images from media relationship
  • Remove duplicates
  ↓
For each image path:
  MediaService->deleteImage($path)
    ↓
    Deletes all versions (original, large, medium, thumbnail)
  ↓
Delete article from database
  ↓
Return success response
```

## Error Handling

- **Image deletion errors are logged** but don't prevent article deletion
- Each image deletion is wrapped in try-catch
- Warnings logged for individual image deletion failures
- Article deletion proceeds even if some/all images fail to delete

## Logging

The system logs:
- **Info**: Successfully deleted images with their paths
- **Warning**: Failed individual image deletions with error messages
- **Error**: General errors in the deletion process

## Testing Recommendations

1. **Test with featured image only**
   - Create article with featured image
   - Delete article
   - Verify image removed from Wasabi

2. **Test with content images**
   - Create article with images in content
   - Delete article
   - Verify all content images removed

3. **Test with multiple translations**
   - Create article with images in Bengali and English content
   - Delete article
   - Verify images from both translations removed

4. **Test with media relationship**
   - Create article with attached media
   - Delete article
   - Verify media files removed

5. **Test permission scenarios**
   - Writer deleting draft (should work)
   - Writer deleting published (should fail before image deletion)
   - Moderator deleting any status (should work)

6. **Test error scenarios**
   - Delete article with already-deleted images (should continue)
   - Delete article with invalid image URLs (should continue)

## Benefits

✅ **Storage savings** - No orphaned files accumulating in Wasabi
✅ **Cost reduction** - Lower storage costs over time
✅ **Clean storage** - Only active article images remain
✅ **Safe deletion** - Article deletion not blocked by image errors
✅ **Comprehensive** - Handles all image sources and versions

## Notes

- Image deletion happens **before** article deletion
- If image deletion fails, article is still deleted (by design)
- All four image versions (original, large, medium, thumbnail) are deleted
- Works for writers and moderators with appropriate permissions
