# Location Search API Documentation

## Endpoint
`GET /api/public/v1/location/search`

## Description
Efficient autocomplete search endpoint for locations. Returns filtered locations based on search query with intelligent relevance ordering.

## Query Parameters

| Parameter | Type    | Required | Default | Validation         | Description                          |
|-----------|---------|----------|---------|-------------------|--------------------------------------|
| `q`       | string  | Yes      | -       | min:2, max:255    | Search query (case-insensitive)      |
| `limit`   | integer | No       | 50      | min:1, max:100    | Maximum number of results to return  |

## Features

### 1. Case-Insensitive Search
- Searches are performed case-insensitively
- Works with Hungarian special characters (á, é, í, ó, ö, ő, ú, ü, ű)

### 2. Smart Relevance Ordering
Results are ordered by:
1. **Importance** (DESC) - Higher importance locations appear first
2. **Relevance**:
   - Exact matches first
   - Starts-with matches second
   - Contains matches last
3. **Alphabetical** (ASC) - For consistent ordering within same relevance

### 3. Performance Optimized
- Database index on `(name, importance)` for fast queries
- Query limit prevents excessive data transfer
- Efficient SQL with minimal overhead

## Response Format

### Success Response (200 OK)
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
      "type": "city",
      "created_at": "2025-09-30T09:38:04.000000Z",
      "updated_at": "2025-09-30T09:38:04.000000Z"
    }
  ]
}
```

### Validation Error (422 Unprocessable Entity)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "q": ["The q field is required."]
  }
}
```

## Example Requests

### Basic Search
```bash
GET /api/public/v1/location/search?q=buda
```

**Returns**: Budapest, Budajenő, Budakalász, Budakeszi, etc.

### With Limit
```bash
GET /api/public/v1/location/search?q=pécs&limit=5
```

**Returns**: Top 5 locations matching "pécs"

### Hungarian Characters
```bash
GET /api/public/v1/location/search?q=győr
```

**Returns**: Győr, Győrasszonyfa, etc.

### No Results
```bash
GET /api/public/v1/location/search?q=nonexistent
```

**Returns**:
```json
{
  "success": true,
  "data": []
}
```

## Error Cases

### Missing Query Parameter
```bash
GET /api/public/v1/location/search
```

**Response (422)**:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "q": ["The q field is required."]
  }
}
```

### Query Too Short
```bash
GET /api/public/v1/location/search?q=a
```

**Response (422)**:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "q": ["The q field must be at least 2 characters."]
  }
}
```

### Invalid Limit
```bash
GET /api/public/v1/location/search?q=budapest&limit=200
```

**Response (422)**:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "limit": ["The limit field must not be greater than 100."]
  }
}
```

## Performance Considerations

### Database Index
The following index is created for optimal performance:
```sql
CREATE INDEX idx_location_search ON locations (name, importance);
```

### Query Optimization
- Uses `LOWER()` for case-insensitive matching
- Composite ordering with CASE statement for relevance
- Limited result set prevents memory issues
- Index usage for fast lookups

### Expected Performance
- ~10,000 total locations
- Search query: < 50ms
- Memory usage: minimal (limited results)
- Scales well with database growth

## Frontend Integration Example

### JavaScript (Fetch API)
```javascript
async function searchLocations(query, limit = 50) {
  const params = new URLSearchParams({ q: query, limit });
  const response = await fetch(`/api/public/v1/location/search?${params}`);
  const data = await response.json();

  if (data.success) {
    return data.data;
  }
  throw new Error(data.message);
}

// Usage
const results = await searchLocations('buda', 10);
console.log(results); // Array of location objects
```

### React with Debounce
```jsx
import { useState, useEffect } from 'react';
import { debounce } from 'lodash';

function LocationSearch() {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(false);

  const searchLocations = debounce(async (q) => {
    if (q.length < 2) {
      setResults([]);
      return;
    }

    setLoading(true);
    try {
      const response = await fetch(
        `/api/public/v1/location/search?q=${encodeURIComponent(q)}&limit=10`
      );
      const data = await response.json();
      if (data.success) {
        setResults(data.data);
      }
    } catch (error) {
      console.error('Search failed:', error);
    } finally {
      setLoading(false);
    }
  }, 300);

  useEffect(() => {
    searchLocations(query);
  }, [query]);

  return (
    <div>
      <input
        type="text"
        value={query}
        onChange={(e) => setQuery(e.target.value)}
        placeholder="Search locations..."
      />
      {loading && <div>Loading...</div>}
      <ul>
        {results.map((loc) => (
          <li key={loc.id}>{loc.name}</li>
        ))}
      </ul>
    </div>
  );
}
```

## Testing

### Manual Testing Checklist
- [x] Search with "buda" returns Budapest first
- [x] Search with "pécs" handles Hungarian characters
- [x] Limit parameter works (verified with limit=2)
- [x] Empty query returns validation error
- [x] Single character returns validation error
- [x] Non-existent location returns empty array
- [x] Special characters handled safely

### Automated Test Examples
```php
// tests/Feature/LocationSearchTest.php
public function test_search_requires_query_parameter()
{
    $response = $this->get('/api/public/v1/location/search');
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['q']);
}

public function test_search_requires_minimum_two_characters()
{
    $response = $this->get('/api/public/v1/location/search?q=a');
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['q']);
}

public function test_search_returns_results()
{
    $response = $this->get('/api/public/v1/location/search?q=buda');
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'success',
                 'data' => [
                     '*' => ['id', 'name', 'slug', 'importance']
                 ]
             ]);
}

public function test_search_respects_limit()
{
    $response = $this->get('/api/public/v1/location/search?q=budapest&limit=2');
    $data = $response->json();
    $this->assertCount(2, $data['data']);
}
```

## Migration Required

The search index migration has been applied:
```bash
php artisan migrate
```

File: `database/migrations/2025_09_30_100452_add_search_index_to_locations_table.php`

## Notes
- The endpoint is publicly accessible (no authentication required)
- Results are not paginated (use `limit` instead)
- Empty results return `200` status with empty array (not `404`)
- Special characters in search query are safely escaped
- Database collation handles Hungarian character comparisons correctly