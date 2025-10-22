# Inki v4 API - Developer Guide

## Table of Contents
1. [Project Overview](#project-overview)
2. [Architecture](#architecture)
3. [Database Structure](#database-structure)
4. [Migration System](#migration-system)
5. [API Endpoints](#api-endpoints)
6. [Module Structure](#module-structure)
7. [Development Workflow](#development-workflow)
8. [Testing](#testing)

---

## Project Overview

Inki v4 is a modern real estate management API built with Laravel, featuring:
- Modular architecture for scalability
- Multi-category property listings (estates)
- Dynamic attribute system for forms
- Location-based search with autocomplete
- Statistics and market analysis
- Blog and news management
- Multi-tenant support (companies, users)

### Tech Stack
- **Framework**: Laravel 11.x
- **Database**: MySQL 8.0+
- **PHP**: 8.2+
- **API**: RESTful JSON API
- **Authentication**: Laravel Sanctum
- **Storage**: DigitalOcean Spaces (S3-compatible)

---

## Architecture

### Modular Structure
The application uses a modular architecture where each feature is isolated in its own module:

```
app/Modules/
├── Attributes/
├── Blog/
├── Categories/
├── Companies/
├── Counties/
├── Estates/
├── Locations/
├── News/
├── Statistics/
└── Users/
```

Each module contains:
- **Controllers**: HTTP request handlers
- **Models**: Eloquent ORM models
- **Resources**: API response transformers
- **Requests**: Form validation classes
- **Migrations**: Database schema
- **Seeders**: Sample data
- **Tests**: Feature/Unit tests

### Key Design Patterns
1. **Repository Pattern**: Database abstraction (if needed)
2. **Resource Pattern**: Consistent API responses
3. **Service Pattern**: Business logic separation
4. **Observer Pattern**: Model events

---

## Database Structure

### Core Tables

#### `estates`
Primary property listings table.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Property title |
| slug | string | URL-friendly identifier |
| description | text | Full property description |
| price | decimal(15,2) | Property price |
| location_id | bigint | FK to locations |
| district_id | bigint | FK to locations (optional) |
| category_id | bigint | FK to categories |
| accepted | boolean | Admin approval status |
| sold | boolean | Sale status |
| published | boolean | Visibility status |
| address | string | Street address |
| zip | string | Postal code |
| custom_attributes | json | Legacy attributes from migration |
| created_at | timestamp | - |
| updated_at | timestamp | - |
| deleted_at | timestamp | Soft delete |

**Indexes:**
- `estates_location_id_index`
- `estates_category_id_index`
- `estates_slug_unique`

#### `categories`
Property types (flat, house, land, etc.)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Category name |
| slug | string | URL identifier |
| description | text | Category description |

**Relationships:**
- `hasMany` estates
- `belongsToMany` attributes (pivot: attribute_category)

#### `attributes`
Dynamic form fields for categories.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Field label |
| slug | string | Field identifier |
| description | text | Help text |
| type | string | Field type (text, select, multiselect) |
| options | json | Select options |

**Field Types:**
- `text`: Single-line text input
- `textarea`: Multi-line text
- `select`: Dropdown (single choice)
- `multiselect`: Multiple choice (checkboxes)

#### `attribute_category` (Pivot)
Links attributes to categories for dynamic forms.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| attribute_id | bigint | FK to attributes |
| category_id | bigint | FK to categories |
| required | boolean | Field is required |
| order | integer | Display order |

#### `locations`
Cities, towns, and districts.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Location name |
| slug | string | URL identifier |
| importance | integer | Search priority (0-100) |
| county_id | bigint | FK to counties |
| type | string | city/town/village |
| has_districts | boolean | Has sub-locations |

**Indexes:**
- `idx_location_search` (name, importance) - For autocomplete

---

## Migration System

### Custom Migration Commands

#### 1. Migrate Old Database
Imports data from legacy `inki_stage` database.

```bash
# Dry run (preview only)
php artisan migrate:old-db --dry-run

# Actual migration
php artisan migrate:old-db

# Custom database
php artisan migrate:old-db --db=other_db_name
```

**What it does:**
- Maps old categories → new categories
- Maps old locations → new locations
- Imports active products → estates
- Preserves all attributes as JSON

**File:** `app/Console/Commands/MigrateOldDatabase.php`

#### 2. Migrate Attributes
Imports form fields from old database.

```bash
# Dry run
php artisan migrate:attributes --dry-run

# Actual migration
php artisan migrate:attributes
```

**What it does:**
- Converts `form_fields` → `attributes`
- Links attributes to categories
- Preserves field types and options

**File:** `app/Console/Commands/MigrateAttributes.php`

### Import Helper
For large SQL file imports:

```bash
php import_old_db.php
```

**File:** `import_old_db.php`

---

## API Endpoints

### Authentication
Base: `/api`

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/register` | Create new user |
| POST | `/login` | Authenticate user |
| POST | `/logout` | Invalidate token |
| GET | `/user` | Get authenticated user |

**Authentication:** Bearer token (Laravel Sanctum)

### Public Endpoints
Base: `/api/public/v1`

#### Estates
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/estate` | List estates (paginated) |
| GET | `/estate/{id}` | Get single estate |

**Query Parameters:**
- `category_id`: Filter by category
- `location_id`: Filter by location
- `price_min`: Minimum price
- `price_max`: Maximum price
- `page`: Page number

#### Categories
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/category` | List all categories |
| GET | `/category/{id}` | Get single category |
| GET | `/category/{id}/attributes` | Get form fields for category |

#### Locations
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/location` | List locations (paginated) |
| GET | `/location/search` | Autocomplete search |

**Search Parameters:**
- `q` (required): Search query (min 2 chars)
- `limit` (optional): Max results (default 50, max 100)

**Example:**
```bash
GET /api/public/v1/location/search?q=buda&limit=10
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Budapest",
      "slug": "budapest",
      "importance": 10,
      "county_id": 13,
      "type": "city"
    }
  ]
}
```

#### Attributes
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/attribute` | List all attributes |
| GET | `/attribute/{id}` | Get single attribute |

#### Counties
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/county` | List all counties |

#### Statistics
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/statistics/pricing-analysis` | Get pricing analysis |
| GET | `/statistics/market-trends` | Market trend data |
| GET | `/statistics/price-distribution` | Price distribution |
| GET | `/statistics/market-insights` | Market insights |
| GET | `/statistics/available-attributes` | Available filter attributes |

#### Blog
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/blog` | List blog posts |
| GET | `/blog/{slug}` | Get single post |

#### News
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/news` | List news articles |
| GET | `/news/{slug}` | Get single article |

### Private Endpoints (Admin)
Base: `/api/private/v1/admin`

**Authentication:** Required (role: admin)

#### Estates
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/estate` | Create estate |
| PUT | `/estate/{id}` | Update estate |
| DELETE | `/estate/{id}` | Delete estate |

#### Categories
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/category` | Create category |
| PUT | `/category/{id}` | Update category |
| DELETE | `/category/{id}` | Delete category |
| POST | `/category/{id}/attributes` | Attach attributes |
| PUT | `/category/{id}/attributes` | Sync attributes |
| DELETE | `/category/{id}/attributes` | Detach attributes |

#### Attributes
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/attribute` | Create attribute |
| PUT | `/attribute/{id}` | Update attribute |
| DELETE | `/attribute/{id}` | Delete attribute |

---

## Module Structure

### Creating a New Module

1. **Create Module Directory**
```bash
mkdir -p app/Modules/NewModule/{Http/{Controllers,Requests,Resources},Models,Database/{Migrations,Seeders},Tests}
```

2. **Create Model**
```php
<?php

namespace App\Modules\NewModule\Models;

use Illuminate\Database\Eloquent\Model;

class NewModel extends Model
{
    protected $fillable = ['name', 'slug'];
}
```

3. **Create Controller**
```php
<?php

namespace App\Modules\NewModule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\NewModule\Models\NewModel;

class NewModelController extends Controller
{
    public function index()
    {
        return NewModel::paginate();
    }
}
```

4. **Add Routes** in `routes/api.php`
```php
Route::prefix('newmodule')->group(function () {
    Route::get('/', [NewModelController::class, 'index']);
});
```

---

## Development Workflow

### Environment Setup

1. **Install Dependencies**
```bash
composer install
npm install
```

2. **Configure Environment**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Database Setup**
```bash
# Update .env with database credentials
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inki_v4_dev
DB_USERNAME=root
DB_PASSWORD=

# Run migrations
php artisan migrate

# Seed data (optional)
php artisan db:seed
```

4. **Start Development Server**
```bash
php artisan serve
# API available at http://localhost:8000
```

### Code Style

Follow PSR-12 coding standards:

```bash
# Check code style
./vendor/bin/phpcs

# Fix code style
./vendor/bin/phpcbf
```

### Git Workflow

```bash
# Create feature branch
git checkout -b feature/new-feature

# Make changes
git add .
git commit -m "Add new feature"

# Push to remote
git push origin feature/new-feature

# Create PR
```

---

## Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter LocationSearchTest

# Run with coverage
php artisan test --coverage
```

### Writing Tests

**Feature Test Example:**
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocationSearchTest extends TestCase
{
    public function test_search_requires_query_parameter()
    {
        $response = $this->get('/api/public/v1/location/search');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['q']);
    }

    public function test_search_returns_results()
    {
        $response = $this->get('/api/public/v1/location/search?q=budapest');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => ['id', 'name', 'slug']
                     ]
                 ]);
    }
}
```

---

## Common Tasks

### Adding a New Attribute Type

1. **Update Attribute Model**
```php
// app/Modules/Attributes/Models/Attribute.php
const TYPES = [
    'text',
    'textarea',
    'select',
    'multiselect',
    'number',      // New type
    'date',        // New type
];
```

2. **Update Validation**
```php
// app/Modules/Attributes/Http/Requests/AttributeRequest.php
'type' => 'required|in:text,textarea,select,multiselect,number,date',
```

3. **Update Frontend**
- Add input component for new type
- Update form builder

### Adding Search Filters

**Example: Add price range filter to estates**

```php
// app/Modules/Estates/Http/Controllers/EstateController.php
public function index()
{
    $query = Estate::query();

    if ($priceMin = request('price_min')) {
        $query->where('price', '>=', $priceMin);
    }

    if ($priceMax = request('price_max')) {
        $query->where('price', '<=', $priceMax);
    }

    return EstateResource::collection($query->paginate());
}
```

### Optimizing Database Queries

**N+1 Query Problem:**
```php
// Bad: N+1 queries
$estates = Estate::all();
foreach ($estates as $estate) {
    echo $estate->category->name; // Extra query per estate
}

// Good: Eager loading
$estates = Estate::with('category')->get();
foreach ($estates as $estate) {
    echo $estate->category->name; // No extra queries
}
```

---

## Troubleshooting

### Common Issues

**Issue: Migration fails with foreign key error**
```
SQLSTATE[23000]: Integrity constraint violation
```

**Solution:** Run migrations in correct order
```bash
php artisan migrate:fresh --seed
```

**Issue: API returns 404**

**Solution:** Clear route cache
```bash
php artisan route:clear
php artisan route:cache
```

**Issue: Changes not reflected**

**Solution:** Clear all caches
```bash
php artisan optimize:clear
```

---

## Resources

### Documentation
- [Laravel Documentation](https://laravel.com/docs)
- [API Design Best Practices](https://restfulapi.net/)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)

### Tools
- [Postman](https://www.postman.com/) - API testing
- [Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar) - Development debugging
- [PHPStan](https://phpstan.org/) - Static analysis

### Related Files
- `LOCATION_SEARCH_API.md` - Location search documentation
- `README.md` - Project overview
- `.env.example` - Environment configuration template

---

## Contributors

For questions or contributions, please refer to the project repository.