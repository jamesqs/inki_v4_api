# Migration Guide - Old Database to Inki v4

## Overview

This guide explains how to migrate data from the legacy Inki database (`inki_stage`) to the new Inki v4 structure.

---

## Prerequisites

1. **Old Database Access**
   - MySQL database named `inki_stage`
   - Same MySQL server as new database
   - Or import SQL file first

2. **New Database Setup**
   - Fresh Laravel installation
   - Migrations run (`php artisan migrate`)
   - Empty or minimal data

---

## Migration Process

### Step 1: Import Old Database SQL

If you have an SQL dump file:

```bash
# Using the provided import script
php import_old_db.php
```

This script:
- Creates `inki_stage` database if not exists
- Imports SQL file with proper error handling
- Handles multi-line comments
- Fixes datetime issues
- Shows progress during import

**Expected Output:**
```
Connecting to MySQL...
Creating database if not exists...
Importing SQL file: /path/to/inki_stage.sql
This may take a few minutes...

Processed 1000 queries (1097 lines)...
Processed 2000 queries (2097 lines)...
...
✅ Import complete!
Lines processed: 410534
Queries executed: 410000
```

### Step 2: Verify Old Database

Check that data was imported correctly:

```bash
php artisan tinker --execute="
echo 'Database verification:' . PHP_EOL;
echo 'Active products: ' . DB::connection()->select('SELECT COUNT(*) as c FROM inki_stage.product WHERE active = 1')[0]->c . PHP_EOL;
echo 'Locations: ' . DB::connection()->select('SELECT COUNT(*) as c FROM inki_stage.location WHERE active = 1')[0]->c . PHP_EOL;
echo 'Categories: ' . DB::connection()->select('SELECT COUNT(*) as c FROM inki_stage.category WHERE active = 1')[0]->c . PHP_EOL;
echo 'Form fields: ' . DB::connection()->select('SELECT COUNT(*) as c FROM inki_stage.form_fields')[0]->c . PHP_EOL;
"
```

**Expected Output:**
```
Database verification:
Active products: 127
Locations: 3182
Categories: 12
Form fields: 71
```

### Step 3: Migrate Data (Dry Run)

Test the migration without making changes:

```bash
# Test product migration
php artisan migrate:old-db --dry-run

# Test attribute migration
php artisan migrate:attributes --dry-run
```

Review the output to ensure mappings are correct.

### Step 4: Run Actual Migration

```bash
# Migrate products/estates
php artisan migrate:old-db

# Migrate form fields/attributes
php artisan migrate:attributes
```

**Expected Output (migrate:old-db):**
```
🚀 Starting migration from inki_stage...

✅ Connected to old database. Found 127 active products.

📍 Step 1: Migrating Locations...
[████████████████████████████] 100%

📁 Step 2: Migrating Categories...
[████████████████████████████] 100%

🏠 Step 3: Migrating Active Products as Estates...
[████████████████████████████] 100%

✅ Migration completed!

+------------+----------+---------+
| Resource   | Imported | Skipped |
+------------+----------+---------+
| Locations  | 0        | 3182    |
| Categories | 8        | 4       |
| Estates    | 127      | 0       |
| Attributes | 712      | -       |
+------------+----------+---------+
```

**Expected Output (migrate:attributes):**
```
🚀 Starting attribute migration from inki_stage...

📁 Step 1: Building category map...
  Mapped 12 categories

🏷️  Step 2: Migrating form fields to attributes...
[████████████████████████████] 100%

✅ Attribute migration completed!

+----------------+---------+---------+
| Resource       | Created | Skipped |
+----------------+---------+---------+
| Attributes     | 18      | 53      |
| Category Links | 71      | 0       |
+----------------+---------+---------+
```

### Step 5: Verify Migration

```bash
# Check migrated data
php artisan tinker --execute="
echo 'Migration verification:' . PHP_EOL;
echo 'Estates: ' . DB::table('estates')->count() . PHP_EOL;
echo 'Estates with attributes: ' . DB::table('estates')->whereNotNull('custom_attributes')->count() . PHP_EOL;
echo 'Categories: ' . DB::table('categories')->count() . PHP_EOL;
echo 'Attributes: ' . DB::table('attributes')->count() . PHP_EOL;
echo 'Category-Attribute links: ' . DB::table('attribute_category')->count() . PHP_EOL;
"
```

**Expected Output:**
```
Migration verification:
Estates: 127
Estates with attributes: 127
Categories: 16
Attributes: 18
Category-Attribute links: 71
```

---

## Data Mapping

### Old → New Structure

#### Products → Estates

| Old Column | New Column | Transformation |
|------------|------------|----------------|
| `id` | - | Used in slug generation |
| `name` | `name` | Direct copy |
| `description` | `description` | Cleaned up |
| `short_description` | - | Merged into description |
| `category_id` | `category_id` | Mapped via slug |
| `location` | `location_id` | Mapped via slug |
| `price` | `price` | EUR converted to HUF (*400) |
| `currency` | - | Normalized to HUF |
| `saletype` | - | Preserved in product data |
| `area` | - | In custom_attributes |
| `address` | `address` | Direct copy |
| `postalcode` | `zip` | Direct copy |
| `active=1` | `published=1` | Active products are published |
| - | `accepted` | Set to true (already approved) |
| - | `sold` | Set to false |
| - | `slug` | Generated from address/name |

#### Product Values → Custom Attributes

All `product_values` are converted to JSON in `custom_attributes`:

```sql
-- Old structure
INSERT INTO product_values (name, value, product_id)
VALUES ('szobak_szama', '3', 32346),
       ('futes_tipusa', 'gáz-cirkó', 32346);

-- New structure
{
  "szobak_szama": "3",
  "futes_tipusa": "gáz-cirkó"
}
```

Multiple values for same attribute become arrays:

```sql
-- Old structure
INSERT INTO product_values (name, value, product_id)
VALUES ('extrak', 'gáz', 32346),
       ('extrak', 'klíma', 32346);

-- New structure
{
  "extrak": ["gáz", "klíma"]
}
```

#### Form Fields → Attributes

| Old Column | New Column | Transformation |
|------------|------------|----------------|
| `id` | - | Not preserved |
| `name` | `name` | Display name |
| `title` | `slug` | Field identifier |
| `type` | `type` | Mapped (see below) |
| `values` | `options` | CSV → JSON array |
| `form_id` | - | Used for category linking |

**Type Mapping:**
- `text` → `text`
- `textarea` → `textarea`
- `select` → `select`
- `multipleselect` → `multiselect`
- `checkbox` → `multiselect`
- `type` → `text`

#### Form Categories → Attribute Category

| Old | New | Note |
|-----|-----|------|
| `form_id` | - | Determines which categories |
| `category_id` | `category_id` | Mapped via slug match |
| - | `attribute_id` | New attribute ID |
| - | `required` | Default false |
| - | `order` | From form_field.id |

---

## Troubleshooting

### Issue: Duplicate entries error

```
SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry
```

**Cause:** Migration was run multiple times

**Solution:** The migration is idempotent and will skip duplicates. This is expected behavior.

### Issue: Category mapping failed

```
Category not found for old category ID: X
```

**Cause:** Category doesn't exist in new database or slug mismatch

**Solution:**
1. Check category slugs match between old and new
2. Manually create missing categories
3. Update category map in migration command

### Issue: Location mapping failed

```
Location not found for old location ID: X
```

**Cause:** Location doesn't exist or has empty name

**Solution:** Migration skips locations with empty names automatically.

### Issue: Database connection error

```
SQLSTATE[HY000] [2002] No such file or directory
```

**Cause:** MySQL using socket instead of TCP

**Solution:** Use `127.0.0.1` instead of `localhost` in `.env`:
```env
DB_HOST=127.0.0.1
```

---

## Post-Migration Tasks

### 1. Verify Data Integrity

```bash
# Check for missing relationships
php artisan tinker --execute="
\$estates = DB::table('estates')
    ->whereNotExists(function(\$query) {
        \$query->select(DB::raw(1))
               ->from('categories')
               ->whereColumn('categories.id', 'estates.category_id');
    })
    ->count();
echo 'Estates with invalid category: ' . \$estates . PHP_EOL;
"
```

### 2. Update Importance Values

Locations have an `importance` field for search ranking:

```sql
-- Set importance for major cities
UPDATE locations SET importance = 10 WHERE slug IN ('budapest', 'debrecen', 'szeged', 'miskolc');
UPDATE locations SET importance = 8 WHERE slug IN ('pécs', 'győr', 'nyíregyháza', 'kecskemét');
UPDATE locations SET importance = 5 WHERE type = 'city' AND importance = 0;
```

### 3. Generate Missing Slugs

```bash
php artisan tinker --execute="
\$estates = DB::table('estates')->whereNull('slug')->get();
foreach (\$estates as \$estate) {
    \$slug = Str::slug(\$estate->address ?: 'property') . '-' . \$estate->id;
    DB::table('estates')->where('id', \$estate->id)->update(['slug' => \$slug]);
}
echo 'Updated ' . count(\$estates) . ' slugs' . PHP_EOL;
"
```

### 4. Clean Up Old Database (Optional)

Once migration is verified, you can remove the old database:

```sql
DROP DATABASE inki_stage;
```

**⚠️ Warning:** Only do this after confirming all data is migrated correctly!

---

## Re-running Migration

The migration commands are **idempotent** - safe to run multiple times:

```bash
# Will skip existing records
php artisan migrate:old-db
php artisan migrate:attributes
```

**What gets skipped:**
- Locations: Checked by slug
- Categories: Checked by slug
- Estates: Checked by slug
- Attributes: Checked by slug
- Attribute-Category links: Checked by both IDs

**What gets re-created:**
- Nothing (all checks prevent duplicates)

---

## Manual Migration (If Needed)

### Export specific data:

```bash
# Export active products
php artisan tinker --execute="
\$products = DB::connection('mysql')
    ->table('inki_stage.product')
    ->where('active', 1)
    ->get();
file_put_contents('products_export.json', json_encode(\$products, JSON_PRETTY_PRINT));
echo 'Exported ' . count(\$products) . ' products' . PHP_EOL;
"
```

### Import manually:

```bash
php artisan tinker --execute="
\$products = json_decode(file_get_contents('products_export.json'));
foreach (\$products as \$product) {
    // Custom import logic
}
"
```

---

## Migration Statistics

Based on actual migration from `inki_stage`:

| Resource | Count | Time |
|----------|-------|------|
| Locations | 3,182 | ~5s |
| Categories | 12 | <1s |
| Estates | 127 | ~3s |
| Attributes | 18 | <1s |
| Attribute Links | 71 | <1s |
| Product Values | 712 | <1s |

**Total Time:** ~10 seconds

**Database Size:**
- Old DB: ~410,000 queries
- Import Time: ~3 minutes
- Disk Space: ~50 MB

---

## Best Practices

1. **Always run dry-run first**
   ```bash
   php artisan migrate:old-db --dry-run
   ```

2. **Backup before migration**
   ```bash
   php artisan db:backup
   ```

3. **Test on staging first**
   - Never run migration on production directly
   - Verify all data in staging environment

4. **Monitor during migration**
   - Watch for errors in console
   - Check database logs
   - Verify sample records

5. **Document custom changes**
   - Note any manual modifications
   - Update migration scripts for future use

---

## Support

For migration issues:
1. Check this guide first
2. Review error messages carefully
3. Verify database connection
4. Check old database structure matches expected format
5. Contact development team if needed