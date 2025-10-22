# File Upload System - Complete Guide

## Overview

The Inki v4 API includes a comprehensive file upload system built with:
- **DigitalOcean Spaces** (S3-compatible storage) for production
- **Local storage** for development
- **Polymorphic relationships** for attaching files to any model
- **Collections** for organizing files by type
- **Metadata support** for additional file information

---

## Table of Contents
1. [Configuration](#configuration)
2. [API Endpoints](#api-endpoints)
3. [File Upload](#file-upload)
4. [Media Management](#media-management)
5. [Frontend Integration](#frontend-integration)
6. [Collections](#collections)
7. [Security](#security)
8. [Troubleshooting](#troubleshooting)

---

## Configuration

### 1. Install Dependencies

The S3 Flysystem adapter is already installed:
```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

### 2. DigitalOcean Spaces Setup

**Create a Space:**
1. Log in to your DigitalOcean account
2. Go to Spaces → Create Space
3. Choose a region (e.g., `fra1` for Frankfurt)
4. Name your space (e.g., `inki-media`)
5. Set access to "Public" for serving files

**Generate API Keys:**
1. Go to API → Spaces Keys
2. Generate New Key
3. Save the Access Key and Secret Key

### 3. Environment Configuration

Add to your `.env` file:

```env
# File Storage
FILESYSTEM_DISK=digitalocean

# DigitalOcean Spaces Configuration
DO_SPACES_KEY=your_access_key_here
DO_SPACES_SECRET=your_secret_key_here
DO_SPACES_REGION=fra1
DO_SPACES_BUCKET=inki-media
DO_SPACES_ENDPOINT=https://fra1.digitaloceanspaces.com
DO_SPACES_URL=https://inki-media.fra1.digitaloceanspaces.com
```

**For Development (Local Storage):**
```env
FILESYSTEM_DISK=local
```

### 4. Storage Configuration

The filesystem config is already set up in `config/filesystems.php`:

```php
'digitalocean' => [
    'driver' => 's3',
    'key' => env('DO_SPACES_KEY'),
    'secret' => env('DO_SPACES_SECRET'),
    'region' => env('DO_SPACES_REGION', 'fra1'),
    'bucket' => env('DO_SPACES_BUCKET'),
    'url' => env('DO_SPACES_URL'),
    'endpoint' => env('DO_SPACES_ENDPOINT'),
    'use_path_style_endpoint' => false,
    'visibility' => 'public',
],
```

---

## API Endpoints

### Public Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/public/v1/media` | List media files | No |
| GET | `/api/public/v1/media/{id}` | Get single media file | No |

### Admin Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/private/v1/admin/media/upload` | Upload file | Admin |
| PUT | `/api/private/v1/admin/media/{id}` | Update metadata | Admin |
| DELETE | `/api/private/v1/admin/media/{id}` | Soft delete file | Admin |
| DELETE | `/api/private/v1/admin/media/{id}/force` | Permanently delete | Admin |

---

## File Upload

### Upload Request

**Endpoint:** `POST /api/private/v1/admin/media/upload`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `file` | file | Yes | The file to upload (max 100MB) |
| `collection` | string | No | File collection (default: 'general') |
| `metadata` | object | No | Additional metadata |
| `mediable_type` | string | No | Model class name |
| `mediable_id` | integer | No | Model ID |

**Example cURL:**
```bash
curl -X POST \
  https://api.inki.test/api/private/v1/admin/media/upload \
  -H 'Authorization: Bearer your-token-here' \
  -F 'file=@/path/to/image.jpg' \
  -F 'collection=estate_images' \
  -F 'metadata[alt]=Beautiful apartment' \
  -F 'metadata[caption]=Main view'
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "id": 1,
    "name": "apartment.jpg",
    "file_name": "550e8400-e29b-41d4-a716-446655440000.jpg",
    "mime_type": "image/jpeg",
    "extension": "jpg",
    "size": 2048576,
    "human_readable_size": "2 MB",
    "disk": "digitalocean",
    "path": "estates/images/2025/01/550e8400-e29b-41d4-a716-446655440000.jpg",
    "url": "https://inki-media.fra1.digitaloceanspaces.com/estates/images/2025/01/550e8400-e29b-41d4-a716-446655440000.jpg",
    "collection": "estate_images",
    "metadata": {
      "alt": "Beautiful apartment",
      "caption": "Main view"
    },
    "is_image": true,
    "is_video": false,
    "is_document": false,
    "created_at": "2025-01-15T10:30:00.000000Z",
    "updated_at": "2025-01-15T10:30:00.000000Z"
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "file": ["The file field is required."]
  }
}
```

---

## Media Management

### List Media Files

**Endpoint:** `GET /api/public/v1/media`

**Query Parameters:**
- `collection` - Filter by collection
- `type` - Filter by type (`image`, `video`, `document`)
- `per_page` - Results per page (default: 15)
- `page` - Page number

**Example:**
```bash
GET /api/public/v1/media?collection=estate_images&type=image&per_page=20
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "apartment.jpg",
      "url": "https://...",
      "collection": "estate_images",
      ...
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 50
  }
}
```

### Get Single Media

**Endpoint:** `GET /api/public/v1/media/{id}`

### Update Metadata

**Endpoint:** `PUT /api/private/v1/admin/media/{id}`

**Body:**
```json
{
  "name": "Updated filename",
  "collection": "blog_images",
  "metadata": {
    "alt": "New alt text",
    "caption": "New caption"
  },
  "order": 1
}
```

### Delete Media

**Soft Delete (Recoverable):**
```bash
DELETE /api/private/v1/admin/media/{id}
```

**Permanent Delete (File removed from storage):**
```bash
DELETE /api/private/v1/admin/media/{id}/force
```

---

## Collections

Collections organize files by their purpose:

| Collection | Path | Description |
|------------|------|-------------|
| `estate_images` | `estates/images/{year}/{month}/` | Property photos |
| `blog_images` | `blog/images/{year}/{month}/` | Blog post images |
| `blog_files` | `blog/files/{year}/{month}/` | Blog attachments |
| `news_images` | `news/images/{year}/{month}/` | News article images |
| `user_avatars` | `users/avatars/` | User profile pictures |
| `company_logos` | `companies/logos/` | Company logos |
| `general` | `uploads/general/{year}/{month}/` | Miscellaneous files |

**Custom collections** follow the pattern: `uploads/{collection}/{year}/{month}/{filename}`

---

## Frontend Integration

### React Example with Axios

```jsx
import { useState } from 'react';
import axios from 'axios';

function FileUpload() {
  const [file, setFile] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [uploadedFile, setUploadedFile] = useState(null);

  const handleUpload = async () => {
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('collection', 'estate_images');
    formData.append('metadata[alt]', 'Property image');

    setUploading(true);

    try {
      const response = await axios.post(
        '/api/private/v1/admin/media/upload',
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          },
          onUploadProgress: (progressEvent) => {
            const percentCompleted = Math.round(
              (progressEvent.loaded * 100) / progressEvent.total
            );
            console.log(`Upload progress: ${percentCompleted}%`);
          }
        }
      );

      setUploadedFile(response.data.data);
      alert('File uploaded successfully!');
    } catch (error) {
      console.error('Upload failed:', error.response?.data);
      alert('Upload failed!');
    } finally {
      setUploading(false);
    }
  };

  return (
    <div>
      <input
        type="file"
        onChange={(e) => setFile(e.target.files[0])}
        accept="image/*"
      />
      <button onClick={handleUpload} disabled={uploading}>
        {uploading ? 'Uploading...' : 'Upload'}
      </button>

      {uploadedFile && (
        <div>
          <h3>Uploaded File:</h3>
          <img src={uploadedFile.url} alt={uploadedFile.name} width="200" />
          <p>URL: {uploadedFile.url}</p>
        </div>
      )}
    </div>
  );
}

export default FileUpload;
```

### Vue 3 Example

```vue
<template>
  <div>
    <input
      type="file"
      @change="handleFileChange"
      accept="image/*"
    />
    <button @click="uploadFile" :disabled="uploading">
      {{ uploading ? 'Uploading...' : 'Upload' }}
    </button>

    <div v-if="uploadedFile">
      <img :src="uploadedFile.url" :alt="uploadedFile.name" width="200">
      <p>{{ uploadedFile.human_readable_size }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const file = ref(null);
const uploading = ref(false);
const uploadedFile = ref(null);

const handleFileChange = (event) => {
  file.value = event.target.files[0];
};

const uploadFile = async () => {
  if (!file.value) return;

  const formData = new FormData();
  formData.append('file', file.value);
  formData.append('collection', 'blog_images');

  uploading.value = true;

  try {
    const { data } = await axios.post(
      '/api/private/v1/admin/media/upload',
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      }
    );

    uploadedFile.value = data.data;
  } catch (error) {
    console.error('Upload failed:', error);
  } finally {
    uploading.value = false;
  }
};
</script>
```

### Drag & Drop Example

```jsx
function DragDropUpload() {
  const [isDragging, setIsDragging] = useState(false);

  const handleDrag = (e) => {
    e.preventDefault();
    e.stopPropagation();
  };

  const handleDragIn = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(true);
  };

  const handleDragOut = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);
  };

  const handleDrop = async (e) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);

    const files = [...e.dataTransfer.files];
    if (files.length > 0) {
      await uploadFile(files[0]);
    }
  };

  const uploadFile = async (file) => {
    // Same upload logic as before
  };

  return (
    <div
      onDragEnter={handleDragIn}
      onDragLeave={handleDragOut}
      onDragOver={handleDrag}
      onDrop={handleDrop}
      style={{
        border: isDragging ? '2px dashed blue' : '2px dashed gray',
        padding: '40px',
        textAlign: 'center'
      }}
    >
      {isDragging ? 'Drop file here' : 'Drag & drop file here'}
    </div>
  );
}
```

---

## Security

### File Validation

**Allowed File Types:**
- Images: jpg, jpeg, png, gif, webp, svg
- Documents: pdf, doc, docx, xls, xlsx
- Videos: mp4, mov, avi, wmv

**File Size Limits:**
- Maximum: 100MB
- Configurable in `MediaController::upload()`

**Security Features:**
- Unique UUID filenames prevent overwriting
- MIME type validation
- File size validation
- Authentication required for uploads
- Soft delete for recovery

### Virus Scanning (Recommended)

For production, integrate a virus scanner:

```php
// In MediaController::upload()
use Illuminate\Support\Facades\Storage;

$virusScan = Storage::disk('local')->virusScan($file);
if ($virusScan->infected) {
    return response()->json([
        'success' => false,
        'message' => 'File contains malware'
    ], 400);
}
```

---

## Polymorphic Relationships

Attach media to any model:

### Estate Example

```php
// app/Modules/Estates/Models/Estate.php
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Estate extends Model
{
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function images(): MorphMany
    {
        return $this->media()->where('collection', 'estate_images');
    }
}

// Usage
$estate = Estate::find(1);
$images = $estate->images;

// Attach media during upload
POST /api/private/v1/admin/media/upload
{
    "file": ...,
    "mediable_type": "App\\Modules\\Estates\\Models\\Estate",
    "mediable_id": 1,
    "collection": "estate_images"
}
```

---

## Troubleshooting

### Issue: Files not uploading

**Check:**
1. File size under 100MB
2. Correct `Content-Type: multipart/form-data`
3. Valid authentication token
4. Storage disk configured correctly

### Issue: 403 Forbidden on DigitalOcean Spaces

**Solution:** Set Space access to "Public" in DigitalOcean console

### Issue: Files upload but URL returns 404

**Check:**
1. Verify `DO_SPACES_URL` in `.env`
2. Ensure Space is set to Public
3. Check CORS settings in DigitalOcean

**CORS Configuration for DigitalOcean Spaces:**
```xml
<CORSConfiguration>
    <CORSRule>
        <AllowedOrigin>*</AllowedOrigin>
        <AllowedMethod>GET</AllowedMethod>
        <AllowedMethod>HEAD</AllowedMethod>
        <AllowedHeader>*</AllowedHeader>
    </CORSRule>
</CORSConfiguration>
```

### Issue: Large files timing out

**Solution:** Increase PHP limits:

```ini
; php.ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
memory_limit = 256M
```

---

## Advanced Usage

### Batch Upload

```javascript
async function uploadMultiple(files) {
  const uploadPromises = files.map(file => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('collection', 'estate_images');

    return axios.post('/api/private/v1/admin/media/upload', formData);
  });

  const results = await Promise.all(uploadPromises);
  return results.map(r => r.data.data);
}
```

### Image Optimization (Future Enhancement)

```php
// Add intervention/image for processing
composer require intervention/image

// In MediaController::upload()
if ($media->isImage()) {
    $image = Image::make($file);
    $image->resize(1920, 1080, function ($constraint) {
        $constraint->aspectRatio();
        $constraint->upsize();
    });
    // Save optimized version
}
```

---

## Database Schema

```sql
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `extension` varchar(10) NOT NULL,
  `size` bigint unsigned NOT NULL,
  `disk` varchar(255) NOT NULL DEFAULT 'digitalocean',
  `path` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `collection` varchar(255) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `mediable_type` varchar(255) DEFAULT NULL,
  `mediable_id` bigint unsigned DEFAULT NULL,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_mediable_type_mediable_id_index` (`mediable_type`,`mediable_id`),
  KEY `media_collection_index` (`collection`),
  KEY `media_disk_index` (`disk`)
);
```

---

## Resources

- [Laravel File Storage](https://laravel.com/docs/11.x/filesystem)
- [DigitalOcean Spaces](https://docs.digitalocean.com/products/spaces/)
- [AWS S3 SDK for PHP](https://docs.aws.amazon.com/sdk-for-php/)
- [Flysystem Documentation](https://flysystem.thephpleague.com/)

---

## Next Steps

1. Configure your `.env` with DigitalOcean Spaces credentials
2. Test upload endpoint with Postman
3. Integrate file upload in your frontend
4. Add virus scanning (optional but recommended)
5. Implement image optimization (optional)
6. Set up CDN for faster delivery (optional)