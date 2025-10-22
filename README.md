# Inki v4 API

Modern real estate management API built with Laravel 11.

## Features

- ✅ **Modular Architecture** - Clean, scalable module structure
- ✅ **RESTful API** - Comprehensive JSON API endpoints
- ✅ **Authentication** - Laravel Sanctum token-based auth
- ✅ **File Upload** - DigitalOcean Spaces (S3) integration
- ✅ **Dynamic Forms** - Category-based attribute system
- ✅ **Location Search** - Fast autocomplete with intelligent ranking
- ✅ **Data Migration** - Tools for migrating from legacy database
- ✅ **Multi-tenant** - Support for companies and users
- ✅ **Statistics** - Market analysis and insights

## Documentation

- **[API Documentation](docs/API_DOCUMENTATION.md)** - OpenAPI/Postman/Scramble guide
- **[Developer Guide](docs/DEVELOPER_GUIDE.md)** - Complete development guide
- **[Migration Guide](docs/MIGRATION_GUIDE.md)** - Database migration guide
- **[File Upload Guide](docs/FILE_UPLOAD_GUIDE.md)** - File upload documentation

### Interactive API Docs

View and test all endpoints: **http://inki.api.test/docs/api**

Generate Postman collection:
```bash
./scripts/generate-api-docs.sh
```

See **[API_DOCS_QUICK_START.md](API_DOCS_QUICK_START.md)** for quick setup.

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

**Full installation guide:** See `docs/DEVELOPER_GUIDE.md`

## API Endpoints

### Public
- `GET /api/public/v1/estate` - List estates
- `GET /api/public/v1/location/search?q=...` - Search locations
- `GET /api/public/v1/category/{id}/attributes` - Get form fields

### Admin (Auth Required)
- `POST /api/private/v1/admin/media/upload` - Upload file
- `POST /api/private/v1/admin/estate` - Create estate

## Custom Commands

```bash
# Migrate old database
php artisan migrate:old-db [--dry-run]

# Migrate form attributes  
php artisan migrate:attributes [--dry-run]
```

## Tech Stack

- Laravel 11.x
- PHP 8.2+
- MySQL 8.0+
- DigitalOcean Spaces (S3)
- Laravel Sanctum

For complete documentation, see `/docs` directory.
