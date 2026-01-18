# Article Images Database Storage Fix

## 🔍 Problem Identified

**Issue**: Featured image path is being saved to the database, but other article images (images embedded in content) are NOT being saved to the `article_media` table.

**Root Cause**: 
- Images uploaded via the `MultiImageUpload` component are converted to markdown format in the content: `![alt](url)`
- Frontend uploads these images to Wasabi S3 and replaces blob URLs with permanent S3 URLs
- The content with image URLs is sent to the backend and saved in `article_translations` table
- **However**, the image metadata was never being saved to the `article_media` table via the `media()` relationship

## ✅ Solution Implemented

### 1. **Added `saveArticleImages()` helper method**
   - Extracts image URLs from markdown content using regex: `![alt](url)`
   - Creates `Media` records for each image
   - Links media to articles via the `article_media` table
   - Stores metadata: sort_order, position, and caption

### 2. **Updated `store()` method (Create Article)**
   ```php
   // After creating article translation
   $this->saveArticleImages($article, $request->content);
   ```

### 3. **Updated `update()` method (Edit Article)**
   ```php
   // Clear existing media links and save new ones
   $article->media()->detach();
   $this->saveArticleImages($article, $request->content);
   ```

### 4. **Updated `Media` model**
   - Added `$fillable` array with all required fields
   - Added `articles()` relationship to link back to articles
   - Full implementation following Laravel conventions

### 5. **Updated imports**
   - Added `use App\Models\Media;` to ContentController

## 📊 Database Schema

### Media Table
```
- id: bigint (primary key)
- title: string (image name/description)
- type: enum('image', 'video', 'audio', 'document')
- file_path: string (Wasabi S3 URL or storage path)
- file_name: string (filename)
- mime_type: string (e.g., 'image/jpeg')
- alt_text: string (alt text for accessibility)
- caption: string (optional caption)
- uploaded_by: foreignId (user who uploaded)
- timestamps
```

### Article_Media Table (Junction)
```
- id: bigint (primary key)
- article_id: foreignId (references articles)
- media_id: foreignId (references media)
- sort_order: integer (order of images in article)
- position: string ('header', 'content', 'gallery', 'thumbnail')
- caption: text (article-specific caption)
- timestamps
- unique constraint on (article_id, media_id)
```

## 🔄 Flow Diagram

```
Frontend (ContributionForm):
  1. User uploads images via MultiImageUpload
  2. Images are uploaded to Wasabi S3
  3. Image URLs are embedded in markdown: ![alt](url)
  4. Form submitted with content containing image URLs

Backend (ContentController):
  1. Article & ArticleTranslation created
  2. saveArticleImages() extracts URLs from markdown
  3. For each URL:
     - Check if Media record exists
     - Create Media record if not
     - Attach to article via article_media table
  4. Article saved with image relationships

Database:
  - articles.featured_image: string (Wasabi URL)
  - article_translations.content: text (markdown with images)
  - media: stores image metadata
  - article_media: links articles to images (sort_order, position)
```

## 🧪 Testing

To verify the fix works:

1. **Create a new article with multiple images**
   - Upload featured image
   - Add multiple images via MultiImageUpload
   - Submit the form

2. **Check database**
   ```php
   $article = Article::with('media')->find($articleId);
   echo $article->media()->count(); // Should show number of images
   
   // Check each media record
   foreach ($article->media as $media) {
       echo $media->title . " | " . $media->pivot->sort_order;
   }
   ```

3. **Verify in API response**
   - GET `/api/content/{id}` should include:
     ```json
     {
       "featured_image": "https://...",
       "media": [
         {
           "id": 1,
           "title": "Image 1",
           "file_path": "https://...",
           "pivot": {
             "sort_order": 0,
             "position": "content"
           }
         }
       ]
     }
     ```

## 🔒 Error Handling

- If image extraction or saving fails, the article is still saved (non-blocking)
- Errors are logged for debugging but don't prevent article creation
- Graceful fallback: articles save even if media linking has issues

## 📝 Files Modified

1. **`app/Http/Controllers/API/ContentController.php`**
   - Added import: `use App\Models\Media;`
   - Added `saveArticleImages()` method
   - Updated `store()` to call `saveArticleImages()`
   - Updated `update()` to detach and re-save media

2. **`app/Models/Media.php`**
   - Added `$fillable` array
   - Added `articles()` relationship

## 🚀 Next Steps

1. Test with new article creation
2. Verify media appears in API responses
3. Frontend can use media relationship to display article images separately
4. Update article detail page to show linked images gallery

## ❌ Known Limitations

- Currently creates new Media records even if same URL exists (could deduplicate in future)
- No width/height detection from Wasabi (could be added later)
- Mime type defaults to 'image/jpeg' (could detect from URL or extension)
- File size defaults to 0 (could fetch from Wasabi metadata)

These can be enhanced in future iterations.
