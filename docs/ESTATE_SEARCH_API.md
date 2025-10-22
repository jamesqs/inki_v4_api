# Estate Search API Documentation

## Endpoint
```
GET /api/public/v1/estate
```

## Overview
This endpoint returns a paginated list of approved estates (properties) with advanced filtering, sorting, and search capabilities.

## Authentication
No authentication required (public endpoint).

## Request Parameters

### Category Filtering
Filter by property category (apartment, house, office, etc.)

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `category_id` | integer | Category ID (legacy) | `18` |
| `category_slug` | string | Category slug (recommended) | `lakas`, `haz`, `iroda` |

**Note:** Use either `category_id` OR `category_slug`, not both.

### Location Filtering
Filter by city/town location

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `location_id` | integer | Location ID (legacy) | `5` |
| `location_slug` | string | Location slug (recommended) | `pecs`, `budapest`, `gyor` |

**Note:** Use either `location_id` OR `location_slug`, not both.

### Listing Type
Filter by sale or rent

| Parameter | Type | Values | Required |
|-----------|------|--------|----------|
| `listing_type` | string | `sale`, `rent` | No |

### Price Range
Filter by price in HUF

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `price_min` | integer | Minimum price | `10000000` (10M HUF) |
| `price_max` | integer | Maximum price | `50000000` (50M HUF) |

### Area Range
Filter by property area in square meters (m²)

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `area_min` | integer | Minimum area in m² | `50` |
| `area_max` | integer | Maximum area in m² | `150` |

**Note:** Area is stored in `custom_attributes.alapterulet`

### Room Filters
Filter by number of rooms/bathrooms

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `bedrooms` | integer | Minimum full bedrooms (egesz-szobak-szama) | `2` means 2+ bedrooms |
| `bathrooms` | integer | Minimum bathrooms (furdoszobak-szama) | `1` means 1+ bathrooms |
| `rooms` | integer | Minimum total rooms (full + half rooms) | `3` means 3+ total rooms |

**Note:** These are stored in `custom_attributes` as:
- `egesz-szobak-szama` (full rooms/bedrooms)
- `fel-szobak-szama` (half rooms)
- `furdoszobak-szama` (bathrooms)

### Custom Attributes
Advanced filtering on any custom attribute

**Range Filter:**
```
attributes[attribute_name][min]=value
attributes[attribute_name][max]=value
```

**Exact Match:**
```
attributes[attribute_name]=value
```

**Examples:**
- `attributes[alapterulet][min]=50&attributes[alapterulet][max]=100` - Area between 50-100 m²
- `attributes[epites-eve][min]=2010` - Built after 2010
- `attributes[lift]=true` - Has elevator
- `attributes[komfort]=duplakomfort` - Exact comfort level

### Sorting

#### New Format (Recommended)
Use the `sort` parameter with predefined values:

| Parameter | Type | Values | Default |
|-----------|------|--------|---------|
| `sort` | string | `newest`, `oldest`, `price_asc`, `price_desc`, `area_asc`, `area_desc` | `newest` |

**Examples:**
- `sort=newest` - Newest properties first (default)
- `sort=oldest` - Oldest properties first
- `sort=price_asc` - Cheapest first
- `sort=price_desc` - Most expensive first
- `sort=area_asc` - Smallest area first
- `sort=area_desc` - Largest area first

#### Legacy Format (Backward Compatible)
Use separate parameters for sorting:

| Parameter | Type | Values | Default |
|-----------|------|--------|---------|
| `sort_by` | string | `created_at`, `price`, etc. | `created_at` |
| `sort_order` | string | `asc`, `desc` | `desc` |

### Pagination

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| `page` | integer | Page number | `1` |
| `per_page` | integer | Items per page | `20` |

### Search

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `name` | string | Search in property name/title | `modern apartment` |

### Special Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `raw` | boolean | Return all results without pagination |

## Response Format

### Success Response
**Status:** 200 OK

```json
{
  "data": [
    {
      "id": 128,
      "name": "Modern lakás Pécsett",
      "slug": "modern-lakas-pecsett-128",
      "description": "Felújított lakás a belvárosban...",
      "price": 45000000,
      "price_type": "fixed",
      "currency": "HUF",
      "formatted_price": "45 000 000 HUF",
      "listing_type": "sale",
      "status": "approved",
      "category": {
        "id": 18,
        "name": "lakás",
        "slug": "lakas"
      },
      "location": {
        "id": 5,
        "name": "Pécs",
        "slug": "pecs",
        "type": "város",
        "county_id": 2
      },
      "custom_attributes": {
        "alapterulet": "75",
        "egesz-szobak-szama": "2",
        "fel-szobak-szama": "1",
        "furdoszobak-szama": "1",
        "lift": true,
        "tipus": "tégla",
        "komfort": "duplakomfort"
      },
      "photos": [
        {
          "id": 1,
          "url": "https://cdn.example.com/photo1.jpg",
          "order": 0
        }
      ],
      "user": {
        "id": 42,
        "name": "John Doe",
        "phone": "+36301234567",
        "profile_picture": {
          "url": "https://cdn.example.com/avatar.jpg"
        }
      },
      "address_data": {
        "zip": "7600",
        "street": "Fő utca",
        "display_mode": "street"
      },
      "views": 156,
      "created_at": "2025-10-15T10:00:00Z",
      "updated_at": "2025-10-20T14:30:00Z"
    }
  ],
  "links": {
    "first": "http://api.url/api/public/v1/estate?page=1",
    "last": "http://api.url/api/public/v1/estate?page=10",
    "prev": null,
    "next": "http://api.url/api/public/v1/estate?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "path": "http://api.url/api/public/v1/estate",
    "per_page": 20,
    "to": 20,
    "total": 195
  }
}
```

## Example Requests

### 1. Basic Category + Location Search
Get all apartments for sale in Pécs:
```
GET /api/public/v1/estate?category_slug=lakas&location_slug=pecs&listing_type=sale
```

### 2. Price Range Filter
Apartments in Budapest between 20M and 40M HUF:
```
GET /api/public/v1/estate?category_slug=lakas&location_slug=budapest&listing_type=sale&price_min=20000000&price_max=40000000
```

### 3. Area + Bedroom Filter
Apartments with 50-100 m² and at least 2 bedrooms:
```
GET /api/public/v1/estate?category_slug=lakas&area_min=50&area_max=100&bedrooms=2
```

### 4. Complete Filter Set
Apartments for rent in Budapest with multiple filters:
```
GET /api/public/v1/estate?category_slug=lakas&location_slug=budapest&listing_type=rent&price_min=100000&price_max=300000&area_min=60&bedrooms=2&bathrooms=1&sort=newest&per_page=12
```

### 5. Custom Attributes Filter
Properties with elevator, built after 2010, brick type:
```
GET /api/public/v1/estate?attributes[lift]=true&attributes[epites-eve][min]=2010&attributes[tipus]=tégla
```

### 6. Sort by Price (Cheapest First)
```
GET /api/public/v1/estate?category_slug=lakas&location_slug=pecs&sort=price_asc
```

### 7. Sort by Area (Largest First)
```
GET /api/public/v1/estate?category_slug=haz&sort=area_desc
```

### 8. Backward Compatible ID-based Search
```
GET /api/public/v1/estate?category_id=18&location_id=5&listing_type=sale
```

### 9. Search by Name
```
GET /api/public/v1/estate?name=modern lakás&location_slug=pecs
```

### 10. Combined Advanced Search
```
GET /api/public/v1/estate?category_slug=lakas&location_slug=budapest&listing_type=sale&price_min=30000000&price_max=60000000&area_min=70&area_max=120&bedrooms=2&bathrooms=1&rooms=3&attributes[lift]=true&attributes[tipus]=tégla&sort=price_asc&per_page=24&page=1
```

## Filter Combinations

### Recommended Filters by Category

**Apartments (lakás):**
- `area_min`, `area_max` (m²)
- `bedrooms` (full rooms)
- `bathrooms`
- `attributes[lift]` (elevator)
- `attributes[komfort]` (comfort level)
- `attributes[epites-eve][min]` (year built)

**Houses (ház):**
- `area_min`, `area_max` (m²)
- `bedrooms`
- `bathrooms`
- `rooms` (total rooms)
- `attributes[kertkapcsolat]` (garden access)
- `attributes[epites-eve][min]`

**Offices (iroda):**
- `area_min`, `area_max` (m²)
- `attributes[lift]`
- `attributes[parkolas]` (parking)

## Performance Notes

1. **Slug vs ID:** Slug-based queries require an extra database lookup but provide better SEO and user experience
2. **JSON Queries:** Filtering on `custom_attributes` uses `JSON_EXTRACT` which may be slower on large datasets
3. **Sorting by Area:** Area sorting uses `CAST(JSON_EXTRACT(...))` which may impact performance
4. **Pagination:** Always use pagination (`per_page`) for better performance
5. **Indexing:** Category, location, price, and created_at fields are indexed for faster queries

## Common Custom Attributes

| Attribute Key | Type | Description | Example Values |
|---------------|------|-------------|----------------|
| `alapterulet` | string | Area in m² | "65", "120" |
| `egesz-szobak-szama` | string | Full rooms/bedrooms | "2", "3" |
| `fel-szobak-szama` | string | Half rooms | "0", "1" |
| `furdoszobak-szama` | string | Bathrooms | "1", "2" |
| `lift` | boolean | Has elevator | `true`, `false` |
| `tipus` | string | Building type | "tégla", "panel" |
| `komfort` | string | Comfort level | "komfort", "duplakomfort" |
| `epites-eve` | string | Year built | "2015", "1998" |
| `parkolas` | string | Parking type | "garázs", "utcán ingyenes" |
| `futes` | string | Heating type | "gázkonvektor", "központi" |
| `kilatas` | string | View type | "panorámás", "utcai" |

## Error Responses

### Invalid Category Slug
**Status:** 200 OK (returns empty results)

If category slug doesn't exist, the filter is ignored and results without category filter are returned.

### Invalid Location Slug
**Status:** 200 OK (returns empty results)

If location slug doesn't exist, the filter is ignored and results without location filter are returned.

### Invalid Sort Parameter
If an invalid `sort` value is provided, defaults to `newest` (created_at desc).

## Best Practices

1. **Use Slugs:** Prefer `category_slug` and `location_slug` over IDs for SEO-friendly URLs
2. **Always Paginate:** Use `per_page` parameter to limit results (default 20, max recommended 100)
3. **Combine Filters:** Multiple filters work together with AND logic
4. **Cache Results:** Consider caching frequently used filter combinations
5. **Frontend URLs:** Build semantic URLs like `/elado/lakas/budapest?min_ar=20000000`

## Frontend URL Mapping Examples

```javascript
// Frontend URL: /elado/lakas/budapest
const apiUrl = `/api/public/v1/estate?listing_type=sale&category_slug=lakas&location_slug=budapest`

// Frontend URL: /kiado/haz/pecs?min_ar=200000&max_ar=500000
const apiUrl = `/api/public/v1/estate?listing_type=rent&category_slug=haz&location_slug=pecs&price_min=200000&price_max=500000`

// Frontend URL: /elado/lakas/gyor?szobak=2&terulat_min=60&rendezes=legolcsobb
const apiUrl = `/api/public/v1/estate?listing_type=sale&category_slug=lakas&location_slug=gyor&bedrooms=2&area_min=60&sort=price_asc`
```

## Changelog

### 2025-10-21
- ✅ Added `category_slug` and `location_slug` support
- ✅ Added `area_min`, `area_max` filtering
- ✅ Added `bedrooms`, `bathrooms`, `rooms` filtering
- ✅ Added new `sort` parameter format (while maintaining backward compatibility)
- ✅ Improved custom attributes filtering with proper type casting
- ✅ Updated response to include slugs in category and location objects
