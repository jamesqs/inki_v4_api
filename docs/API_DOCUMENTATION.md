# API Documentation Guide

This guide explains how to generate, maintain, and use the API documentation for the Inki API.

## Overview

The Inki API uses **Scramble** to automatically generate comprehensive API documentation from your Laravel routes, controllers, and request validation rules. No PHPDoc annotations are required - Scramble analyzes your code automatically.

## Generated Documentation

Three types of documentation are generated:

1. **Interactive Web UI** - Beautiful, interactive API documentation at `/docs/api`
2. **OpenAPI Specification** - Industry-standard `api.json` file
3. **Postman Collection** - Ready-to-import `postman_collection.json` file

## Quick Start

### View Interactive Documentation

Visit the interactive documentation in your browser:

```
http://inki.api.test/docs/api
```

Features:
- 🔍 Search all endpoints
- 📖 View request/response examples
- 🧪 Try out endpoints directly (with authentication)
- 📱 Responsive design
- 🌓 Light/dark mode support

### Generate Documentation Files

Generate both OpenAPI spec and Postman collection:

```bash
./scripts/generate-api-docs.sh
```

Or manually:

```bash
# Generate OpenAPI spec
php artisan scramble:export

# Convert to Postman collection
openapi2postmanv2 -s api.json -o postman_collection.json -p
```

### Import to Postman

1. Open Postman desktop app or web
2. Click **"Import"** button (top left)
3. Select **"postman_collection.json"**
4. Click **"Import"**

Your entire API will be organized into folders matching your route structure:
```
Inki API Documentation
├── public/v1
│   ├── attribute
│   │   ├── GET    /attribute
│   │   ├── POST   /attribute
│   │   └── ...
│   ├── category
│   ├── estate
│   ├── location
│   └── media
└── admin/v1
    └── ...
```

## Configuration

### Scramble Configuration

Configuration file: `config/scramble.php`

#### Key Settings

**API Information:**
```php
'info' => [
    'version' => env('API_VERSION', '1.0.0'),
    'description' => 'Modern real estate management API...',
],
```

**API Path Matching:**
```php
'api_path' => 'api', // All routes starting with /api
```

**Export Path:**
```php
'export_path' => 'api.json', // Where OpenAPI spec is saved
```

**UI Customization:**
```php
'ui' => [
    'title' => 'Inki API Documentation',
    'theme' => 'system', // light, dark, or system
    'hide_try_it' => false, // Enable "Try It" feature
    'layout' => 'responsive', // sidebar, responsive, or stacked
],
```

**Access Control:**
```php
'middleware' => [
    'web',
    RestrictedDocsAccess::class, // Only local access by default
],
```

### Environment Variables

Add to `.env`:

```env
API_VERSION=1.0.0
```

### Server URLs

By default, Scramble uses your app URL. To add multiple servers (local, staging, production):

```php
'servers' => [
    'Local' => 'api',
    'Staging' => 'https://staging.inki.com/api',
    'Production' => 'https://api.inki.com/api',
],
```

## How Scramble Works

Scramble automatically generates documentation by analyzing:

### 1. Route Definitions

```php
// routes/api.php
Route::prefix('public/v1')->group(function () {
    Route::apiResource('estate', EstateController::class);
});
```

Generates endpoints:
- `GET /api/public/v1/estate` - List estates
- `POST /api/public/v1/estate` - Create estate
- `GET /api/public/v1/estate/{estate}` - Show estate
- `PUT/PATCH /api/public/v1/estate/{estate}` - Update estate
- `DELETE /api/public/v1/estate/{estate}` - Delete estate

### 2. Form Request Validation

```php
// EstateRequest.php
public function rules(): array
{
    return [
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
        'location_id' => 'required|exists:locations,id',
    ];
}
```

Scramble extracts:
- Required fields
- Field types (string, number, boolean)
- Validation constraints (min, max, format)
- Relationships (exists:locations)

### 3. Resource Classes

```php
// EstateResource.php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'slug' => $this->slug,
        'price' => $this->price,
        'location' => new LocationResource($this->whenLoaded('location')),
        'created_at' => $this->created_at,
    ];
}
```

Scramble generates response schema with:
- All fields and their types
- Nested resources
- Conditional fields (whenLoaded)

### 4. Controller Methods

```php
public function index(): AnonymousResourceCollection
{
    $estates = Estate::paginate();
    return EstateResource::collection($estates);
}
```

Scramble determines:
- Response type (paginated collection)
- Resource wrapper
- HTTP status codes

### 5. Query Parameters

```php
if ($name = request('name')) {
    $query->where('name', 'like', "%{$name}%");
}
```

Scramble detects:
- Optional query parameters
- Parameter types
- Search/filter capabilities

## Enhancing Documentation

While Scramble works automatically, you can enhance documentation with PHPDoc when needed:

### Add Endpoint Descriptions

```php
/**
 * Search estates by location, category, and price range.
 *
 * Returns a paginated list of estates matching the search criteria.
 * All parameters are optional - without parameters, returns all estates.
 *
 * @queryParam name string Filter by estate name (partial match)
 * @queryParam location_id integer Filter by location ID
 * @queryParam min_price number Minimum price filter
 * @queryParam max_price number Maximum price filter
 */
public function index(): AnonymousResourceCollection
{
    // ...
}
```

### Document Response Examples

```php
/**
 * @response 200 {
 *   "data": [{
 *     "id": 1,
 *     "name": "Modern Apartment in Budapest",
 *     "price": 50000000,
 *     "location": {
 *       "id": 1,
 *       "name": "Budapest"
 *     }
 *   }]
 * }
 */
```

### Document Error Responses

```php
/**
 * @response 404 {
 *   "message": "Estate not found"
 * }
 *
 * @response 422 {
 *   "message": "Validation failed",
 *   "errors": {
 *     "name": ["The name field is required"]
 *   }
 * }
 */
```

## Authentication Documentation

Scramble automatically detects Sanctum authentication:

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('estate', EstateController::class);
});
```

Documentation shows:
- 🔒 Locked icon for protected endpoints
- Authentication requirements
- Token format (Bearer token)

### Testing with Authentication in Postman

1. Get an API token:
```bash
php artisan tinker
>>> $user = User::first();
>>> $token = $user->createToken('test')->plainTextToken;
```

2. In Postman, set Authorization:
   - Type: `Bearer Token`
   - Token: `{your-token}`

## Postman Collection Features

The generated Postman collection includes:

### Organized Structure

Endpoints are grouped by:
- API version (public/v1, admin/v1)
- Resource type (estates, locations, categories)
- CRUD operations

### Variables

Collection variables you can set:
- `baseUrl` - API base URL (default: your app URL)
- `token` - Bearer token for authentication
- `version` - API version

### Request Examples

Each endpoint includes:
- All required and optional parameters
- Example request bodies
- Query parameter options
- Header requirements

### Tests (Optional Enhancement)

You can add Postman tests to requests:

```javascript
// Test: Status code is 200
pm.test("Status is 200", function () {
    pm.response.to.have.status(200);
});

// Test: Response has data
pm.test("Response has data", function () {
    const json = pm.response.json();
    pm.expect(json).to.have.property('data');
});

// Save token from login
const json = pm.response.json();
pm.collectionVariables.set("token", json.token);
```

## Updating Documentation

### When to Regenerate

Regenerate documentation after:
- Adding new routes
- Modifying request validation rules
- Changing API responses
- Updating resource classes
- Changing authentication requirements

### Automatic Regeneration

The interactive docs at `/docs/api` are **always up-to-date** - they're generated on-demand from your current code.

### Manual File Regeneration

When you need to share Postman collection or OpenAPI spec:

```bash
./scripts/generate-api-docs.sh
```

### Git Workflow

Add to `.gitignore`:
```
/api.json
/postman_collection.json
```

Why? These files are **generated artifacts** that should be regenerated when needed, not committed to version control.

### Sharing with Team

**Option 1: Regenerate Locally**
```bash
./scripts/generate-api-docs.sh
# Share postman_collection.json via Slack/email
```

**Option 2: Host OpenAPI Spec**
```bash
# Copy api.json to public directory
cp api.json public/api-spec.json

# Share URL
https://api.inki.com/api-spec.json
```

Postman can import directly from URL: `Import → Link`

**Option 3: Postman Workspace**
1. Import collection to Postman
2. Create team workspace
3. Share collection with team

## Advanced Configuration

### Custom Route Matching

If you need more control over which routes are documented:

```php
// config/scramble.php
use Dedoc\Scramble\Scramble;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;

// In a service provider
Scramble::routes(function (Route $route) {
    return Str::startsWith($route->uri, 'api/') &&
           !Str::startsWith($route->uri, 'api/internal/');
});
```

### Ignoring Routes

```php
use Dedoc\Scramble\Attributes\Ignore;

#[Ignore]
class InternalController extends Controller
{
    // Not documented
}
```

Or specific methods:

```php
#[Ignore]
public function debug()
{
    // Not documented
}
```

### Adding Extensions

Scramble supports extensions for custom behavior:

```php
'extensions' => [
    \App\OpenApi\CustomExtension::class,
],
```

## Troubleshooting

### Issue: Documentation Not Showing Routes

**Check:**
1. Routes start with `api/` path
2. Controllers are in proper namespace
3. Clear cache: `php artisan config:clear`

### Issue: Wrong Response Schema

**Solution:**
Ensure your resource classes have proper type hints:

```php
// ❌ Bad - no return type
public function toArray($request)
{
    return ['id' => $this->id];
}

// ✅ Good - with return type
public function toArray(Request $request): array
{
    return ['id' => $this->id];
}
```

### Issue: Missing Query Parameters

**Solution:**
Use FormRequest with query validation:

```php
public function rules(): array
{
    return [
        'name' => 'sometimes|string',
        'location_id' => 'sometimes|exists:locations,id',
    ];
}
```

### Issue: Postman Import Fails

**Cause:** OpenAPI spec version incompatibility

**Solution:**
1. Update `openapi-to-postmanv2`: `npm install -g openapi-to-postmanv2@latest`
2. Try importing OpenAPI spec directly in Postman (supports OpenAPI 3.1)

### Issue: Documentation Accessible in Production

**Security Risk!** By default, docs are only accessible locally.

**Check middleware:**
```php
// config/scramble.php
'middleware' => [
    'web',
    RestrictedDocsAccess::class, // Only local by default
],
```

For production access with authentication:
```php
'middleware' => [
    'web',
    'auth', // Require login
    // Or custom middleware
],
```

## Resources

### Scramble Documentation
- Official Docs: https://scramble.dedoc.co/
- GitHub: https://github.com/dedoc/scramble
- How It Works: https://scramble.dedoc.co/developers/how-it-works

### OpenAPI Specification
- OpenAPI 3.1 Spec: https://spec.openapis.org/oas/v3.1.0
- Swagger Editor: https://editor.swagger.io/

### Postman
- Postman Learning Center: https://learning.postman.com/
- Import Data: https://learning.postman.com/docs/getting-started/importing-and-exporting-data/

## Best Practices

### 1. Use Type Hints

Always add return types to controller methods:
```php
public function index(): AnonymousResourceCollection
public function store(EstateRequest $request): EstateResource
public function show(Estate $estate): EstateResource
```

### 2. Use Form Requests

Create dedicated FormRequest classes instead of inline validation:
```php
// ✅ Good - documented automatically
public function store(EstateRequest $request): EstateResource

// ❌ Bad - Scramble can't detect validation
public function store(Request $request): EstateResource
{
    $validated = $request->validate([...]);
}
```

### 3. Use API Resources

Always use Resource classes for responses:
```php
// ✅ Good - schema extracted automatically
return new EstateResource($estate);

// ❌ Bad - manual schema definition needed
return response()->json($estate);
```

### 4. Group Routes Logically

Use route groups with prefixes:
```php
Route::prefix('public/v1')->group(function () {
    Route::apiResource('estate', EstateController::class);
});
```

Results in clean URLs: `/api/public/v1/estate`

### 5. Version Your API

Use version prefixes for future-proofing:
```php
Route::prefix('v1')->group(function () { /* ... */ });
Route::prefix('v2')->group(function () { /* ... */ });
```

### 6. Add Comments Where Needed

For complex endpoints, add PHPDoc comments:
```php
/**
 * Search estates with advanced filters.
 *
 * Supports full-text search, geographic filtering,
 * and price range queries with relevance ranking.
 */
public function search(SearchRequest $request): AnonymousResourceCollection
```

### 7. Test Documentation

After regenerating, verify in Postman:
1. Import collection
2. Test each endpoint
3. Check request/response examples
4. Validate authentication works

### 8. Document Enums

Use Laravel enums with doc comments:
```php
/**
 * Estate sale types
 */
enum SaleType: string
{
    /** Available for purchase */
    case SALE = 'sale';

    /** Available for rental */
    case RENT = 'rent';
}
```

## Comparison with Alternatives

| Feature | Scramble | Scribe | L5-Swagger |
|---------|----------|--------|------------|
| Zero Annotations | ✅ Yes | ⚠️ Partial | ❌ No |
| OpenAPI 3.1 | ✅ Yes | ✅ Yes | ❌ 3.0 only |
| Laravel 11 Support | ✅ Yes | ✅ Yes | ✅ Yes |
| Interactive UI | ✅ Beautiful | ✅ Good | ✅ Basic |
| Auto-detect Validation | ✅ Yes | ✅ Yes | ❌ Manual |
| Postman Export | ✅ Via converter | ✅ Built-in | ✅ Via converter |
| Learning Curve | ⭐⭐ Easy | ⭐⭐⭐ Medium | ⭐⭐⭐⭐ Hard |

**We chose Scramble because:**
- Zero configuration needed
- Automatically detects everything
- Beautiful, modern UI
- Best Laravel 11 support
- Active development

---

**Generated with Scramble v0.12.35**
**Last Updated:** October 2025
