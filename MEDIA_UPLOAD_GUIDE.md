# Wasabi Media Upload Implementation Guide

## Overview
এই implementation Wasabi S3-compatible storage ব্যবহার করে multimedia files (images, videos, documents) upload এবং optimize করে।

## Features

### 1. Image Optimization
- **Automatic Resizing**: 4টি different sizes তৈরি হয়:
  - `original`: Quality-optimized original
  - `large`: Max 1920px
  - `medium`: Max 800px
  - `thumbnail`: Max 300px

- **Quality Settings**: 
  - JPEG quality: 85% (optimal balance)
  - Automatic format conversion
  - Progressive JPEG encoding

- **File Size Limits**:
  - Images: Max 10MB
  - Videos: Max 100MB
  - Documents: Max 50MB

### 2. Supported Formats

#### Images
- JPEG / JPG
- PNG
- GIF
- WebP

#### Videos
- MP4
- MPEG
- MOV (QuickTime)
- AVI
- WebM

## Backend Setup

### 1. Environment Configuration
`.env` file এ Wasabi credentials:
```env
WASABI_KEY=TR41Q8K5LJFNAPAPQLHZ
WASABI_SECRET=PMeC4kUd96BfAs0BGAvheTw95nzOiB9fnIeYH6Ij
WASABI_REGION=ap-southeast-1
WASABI_BUCKET=oshongshoy
WASABI_ENDPOINT=https://s3.ap-southeast-1.wasabisys.com
```

### 2. Filesystem Configuration
`config/filesystems.php` তে Wasabi disk added:
```php
'wasabi' => [
    'driver' => 's3',
    'key' => env('WASABI_KEY'),
    'secret' => env('WASABI_SECRET'),
    'region' => env('WASABI_REGION'),
    'bucket' => env('WASABI_BUCKET'),
    'endpoint' => env('WASABI_ENDPOINT'),
],
```

### 3. Required Packages
```bash
composer require intervention/image
composer require league/flysystem-aws-s3-v3 "^3.0"
```

## API Endpoints

### Upload Image
```http
POST /api/media/upload/image
Content-Type: multipart/form-data
Authorization: Bearer {token}

Parameters:
- file: Image file (required)
- folder: Storage folder (optional, default: 'images')
```

**Response:**
```json
{
  "success": true,
  "message": "Image uploaded successfully",
  "data": {
    "url": "https://s3.ap-southeast-1.wasabisys.com/oshongshoy/images/original/abc123.jpg",
    "urls": {
      "original": "https://..../original/abc123.jpg",
      "large": "https://..../large/abc123.jpg",
      "medium": "https://..../medium/abc123.jpg",
      "thumbnail": "https://..../thumbnails/abc123.jpg"
    },
    "filename": "abc123.jpg",
    "path": "images/original/abc123.jpg",
    "size": 1024000,
    "mime_type": "image/jpeg",
    "width": 1920,
    "height": 1080
  }
}
```

### Upload Video
```http
POST /api/media/upload/video
Content-Type: multipart/form-data
Authorization: Bearer {token}

Parameters:
- file: Video file (required)
- folder: Storage folder (optional, default: 'videos')
```

### Upload File
```http
POST /api/media/upload/file
Content-Type: multipart/form-data
Authorization: Bearer {token}

Parameters:
- file: Any file (required)
- folder: Storage folder (optional, default: 'documents')
```

### Delete File
```http
DELETE /api/media/delete
Content-Type: application/json
Authorization: Bearer {token}

Body:
{
  "path": "images/original/abc123.jpg",
  "type": "image" // optional: image, video, file
}
```

## Frontend Integration

### Using ImageUpload Component

```tsx
import ImageUpload from '@/components/ImageUpload';

function MyForm() {
  const [featuredImage, setFeaturedImage] = useState('');

  const handleImageUpload = (url: string, data: any) => {
    setFeaturedImage(url);
    console.log('Upload data:', data);
  };

  return (
    <ImageUpload
      onUpload={handleImageUpload}
      folder="articles"
      label="Featured Image"
      currentImage={featuredImage}
    />
  );
}
```

### Direct API Call

```typescript
const uploadImage = async (file: File) => {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('folder', 'articles');

  const response = await api.post('/media/upload/image', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });

  return response.data.data;
};
```

## MediaService Class Methods

### `uploadImage(file, folder)`
Image upload এবং multiple sizes generate করে।

**Parameters:**
- `file`: UploadedFile instance
- `folder`: Storage folder name (default: 'images')

**Returns:**
```php
[
    'url' => 'original_url',
    'urls' => ['original', 'large', 'medium', 'thumbnail'],
    'filename' => 'generated_filename',
    'path' => 'storage_path',
    'size' => file_size,
    'mime_type' => 'image/jpeg',
    'width' => image_width,
    'height' => image_height,
]
```

### `uploadVideo(file, folder)`
Video file upload করে (no processing)।

### `uploadFile(file, folder)`
Any file upload করে।

### `deleteImage(path)`
Image এবং all versions delete করে।

### `deleteFile(path)`
Single file delete করে।

## Optimization Details

### Image Processing
1. **Original**: Quality 85% JPEG
2. **Large**: Scaled to max 1920px, quality 85%
3. **Medium**: Scaled to max 800px, quality 85%
4. **Thumbnail**: Scaled to max 300px, quality 85%

### Benefits
- **Bandwidth Saving**: Smaller files = faster loading
- **Responsive Images**: Different sizes for different screens
- **SEO Friendly**: Faster page load = better ranking
- **Cost Efficient**: Less storage & bandwidth usage

## Storage Structure

```
oshongshoy (bucket)
├── images/
│   ├── original/
│   │   └── abc123.jpg
│   ├── large/
│   │   └── abc123.jpg
│   ├── medium/
│   │   └── abc123.jpg
│   └── thumbnails/
│       └── abc123.jpg
├── videos/
│   └── video123.mp4
└── documents/
    └── doc123.pdf
```

## Error Handling

### Common Errors

1. **File Too Large**
```json
{
  "success": false,
  "message": "Image size exceeds maximum allowed size of 10MB"
}
```

2. **Invalid File Type**
```json
{
  "success": false,
  "message": "Invalid image type. Allowed: JPEG, PNG, GIF, WebP"
}
```

3. **Upload Failed**
```json
{
  "success": false,
  "message": "Failed to upload image",
  "error": "Network error"
}
```

## Testing

### Using cURL

```bash
# Upload image
curl -X POST http://localhost:8088/api/media/upload/image \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@/path/to/image.jpg" \
  -F "folder=articles"

# Delete image
curl -X DELETE http://localhost:8088/api/media/delete \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"path":"images/original/abc123.jpg","type":"image"}'
```

### Using Postman
1. Create new POST request to `http://localhost:8088/api/media/upload/image`
2. Add Authorization header: `Bearer YOUR_TOKEN`
3. Select Body → form-data
4. Add key `file` with type `File` and select image
5. Add key `folder` with value `articles`
6. Send request

## Best Practices

1. **Always use specific folders**: Group by content type
   ```php
   // Good
   uploadImage($file, 'articles');
   uploadImage($file, 'profiles');
   
   // Bad
   uploadImage($file, 'images');
   ```

2. **Store paths in database**: Save the path for later deletion
   ```php
   $result = $mediaService->uploadImage($file);
   $article->featured_image = $result['url'];
   $article->featured_image_path = $result['path']; // For deletion
   ```

3. **Clean up on delete**: Delete images when deleting content
   ```php
   if ($article->featured_image_path) {
       $mediaService->deleteImage($article->featured_image_path);
   }
   $article->delete();
   ```

4. **Use appropriate sizes**: Choose right size for context
   ```tsx
   // Article card - use thumbnail
   <img src={article.urls.thumbnail} />
   
   // Article detail - use medium/large
   <img src={article.urls.large} />
   ```

## Security Considerations

1. **Authentication Required**: All upload endpoints protected by Sanctum
2. **File Validation**: Type and size validated
3. **Unique Filenames**: Random 40-character filenames prevent conflicts
4. **Public Access**: Files are publicly accessible via URL (no auth needed for viewing)

## Troubleshooting

### Image Not Uploading
1. Check Wasabi credentials in `.env`
2. Verify bucket permissions (public read)
3. Check Laravel logs: `storage/logs/laravel.log`
4. Verify packages installed: `composer show`

### Image Quality Issues
Adjust quality in `MediaService.php`:
```php
const IMAGE_QUALITY = 85; // Change to 90 for higher quality
```

### Storage Costs
Monitor Wasabi usage:
- 1TB storage = $5.99/month
- Egress (downloads) free up to storage amount
- No API charges

## Future Enhancements

1. **Video Processing**: Add video thumbnail generation
2. **WebP Conversion**: Convert all images to WebP
3. **CDN Integration**: Add CloudFlare CDN
4. **Lazy Loading**: Implement progressive image loading
5. **Image Filters**: Add Instagram-style filters
