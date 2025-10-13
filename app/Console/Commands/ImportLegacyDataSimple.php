<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Modules\Estates\Models\Estate;
use App\Modules\Categories\Models\Category;
use App\Modules\Locations\Models\Location;

class ImportLegacyDataSimple extends Command
{
    protected $signature = 'import:legacy-data-simple {--file=inki_stage.sql} {--limit=0} {--only-active}';
    protected $description = 'Import ALL legacy data from inki_stage.sql (simple line-by-line parser)';

    private array $categoryMapping = [];

    public function handle(): int
    {
        $filePath = base_path($this->option('file'));
        $limit = (int) $this->option('limit');
        $onlyActive = $this->option('only-active');

        if (!file_exists($filePath)) {
            $this->error("SQL file not found: {$filePath}");
            return 1;
        }

        $this->info('Starting simple legacy data import...');

        // Import categories first
        $this->info('Importing categories...');
        $this->importCategories();

        // Get default location
        $defaultLocation = Location::first();
        if (!$defaultLocation) {
            $this->error('No locations found. Please ensure locations are seeded first.');
            return 1;
        }

        // Process the file line by line
        $this->info('Processing products...');
        $imported = $this->processProductsFromFile($filePath, $defaultLocation, $limit, $onlyActive);

        $this->info("Import completed! Imported {$imported} estates total.");
        return 0;
    }

    private function processProductsFromFile(string $filePath, Location $defaultLocation, int $limit, bool $onlyActive): int
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error('Could not open SQL file');
            return 0;
        }

        $imported = 0;
        $skipped = 0;
        $errors = 0;
        $lineNumber = 0;

        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            $line = trim($line);

            // Skip non-product lines
            if (!str_starts_with($line, 'INSERT INTO `product`')) {
                continue;
            }

            try {
                if ($this->processProductLine($line, $defaultLocation, $onlyActive)) {
                    $imported++;

                    // Show progress every 100 imports
                    if ($imported % 100 == 0) {
                        $this->info("Imported {$imported} estates so far... (line {$lineNumber})");
                    }

                    // Apply limit if set
                    if ($limit > 0 && $imported >= $limit) {
                        break;
                    }
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors++;
                if ($errors <= 10) { // Only show first 10 errors
                    $this->error("Error on line {$lineNumber}: " . $e->getMessage());
                }
            }
        }

        fclose($handle);

        $this->info("Final results: {$imported} imported, {$skipped} skipped, {$errors} errors");
        return $imported;
    }

    private function processProductLine(string $line, Location $defaultLocation, bool $onlyActive): bool
    {
        // Extract VALUES content using regex
        if (!preg_match('/VALUES\s*\((.*)\);?$/', $line, $matches)) {
            return false;
        }

        $values = $matches[1];

        // Parse the values - since they're comma-separated with quotes, we need to be careful
        $fields = $this->parseProductValues($values);

        if (count($fields) < 30) { // Ensure we have all required fields
            return false;
        }

        // Extract data from fields (based on the CREATE TABLE structure)
        $productData = [
            'id' => (int) $this->cleanValue($fields[0]),
            'name' => $this->cleanValue($fields[1]),
            'description' => $this->cleanValue($fields[5]), // Main description
            'category_id' => $this->cleanValue($fields[7]),
            'price' => $this->cleanValue($fields[9]),
            'currency' => $this->cleanValue($fields[10]) ?: 'huf',
            'saletype' => $this->cleanValue($fields[12]),
            'area' => $this->cleanValue($fields[14]) ?: 0,
            'owner_name' => $this->cleanValue($fields[15]),
            'owner_email' => $this->cleanValue($fields[16]),
            'owner_phone' => $this->cleanValue($fields[17]),
            'created' => $this->cleanValue($fields[18]),
            'address' => $this->cleanValue($fields[21]),
            'postalcode' => $this->cleanValue($fields[20]),
            'active' => (int) $this->cleanValue($fields[27]),
        ];

        // Skip inactive products if only-active flag is set
        if ($onlyActive && $productData['active'] != 1) {
            return false;
        }

        // Skip if no description
        if (empty($productData['description'])) {
            return false;
        }

        // Handle very long descriptions by truncating them
        $description = $productData['description'];
        if (strlen($description) > 50000) { // Limit to 50KB for TEXT field
            $description = substr($description, 0, 50000) . '...';
        }

        // Map category
        $categoryId = $this->categoryMapping[$productData['category_id']] ?? null;
        if (!$categoryId) {
            return false; // Skip if category not found
        }

        // Generate name and slug
        $name = $productData['name'] ?: $this->generateNameFromDescription($productData['description']);
        $slug = Str::slug($name) . '-' . $productData['id'];

        // Make sure slug is unique
        $originalSlug = $slug;
        $counter = 1;
        while (Estate::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Build custom attributes
        $customAttributes = [
            'area' => $productData['area'],
            'currency' => $productData['currency'],
            'sale_type' => $productData['saletype'],
            'original_id' => $productData['id'],
        ];

        // Add owner information if available
        if (!empty($productData['owner_name'])) {
            $customAttributes['owner_name'] = $productData['owner_name'];
        }
        if (!empty($productData['owner_email'])) {
            $customAttributes['owner_email'] = $productData['owner_email'];
        }
        if (!empty($productData['owner_phone'])) {
            $customAttributes['owner_phone'] = $productData['owner_phone'];
        }

        // Handle price
        $price = !empty($productData['price']) && $productData['price'] > 0 ? $productData['price'] : null;

        // Create estate
        Estate::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'price' => $price,
            'location_id' => $defaultLocation->id,
            'category_id' => $categoryId,
            'accepted' => true,
            'sold' => false,
            'published' => $productData['active'] == 1,
            'address' => $productData['address'],
            'zip' => $productData['postalcode'],
            'custom_attributes' => $customAttributes,
            'created_at' => $productData['created'] ?? now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    private function parseProductValues(string $values): array
    {
        $fields = [];
        $current = '';
        $inQuote = false;
        $quoteChar = '';
        $i = 0;

        while ($i < strlen($values)) {
            $char = $values[$i];

            if (!$inQuote && ($char === '"' || $char === "'")) {
                $inQuote = true;
                $quoteChar = $char;
            } elseif ($inQuote && $char === $quoteChar) {
                // Check if it's escaped (doubled quote)
                if ($i + 1 < strlen($values) && $values[$i + 1] === $quoteChar) {
                    $current .= $char;
                    $i++; // Skip the next quote
                } else {
                    $inQuote = false;
                    $quoteChar = '';
                }
            } elseif (!$inQuote && $char === ',') {
                $fields[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
            $i++;
        }

        // Add the last field
        $fields[] = trim($current);

        return $fields;
    }

    private function cleanValue(?string $value): ?string
    {
        if ($value === null || strtoupper($value) === 'NULL' || $value === '') {
            return null;
        }

        // Remove surrounding quotes
        $value = trim($value, "'\"");

        return $value === '' ? null : $value;
    }

    private function importCategories(): void
    {
        $categories = [
            ['id' => 1, 'name' => 'Ház', 'slug' => 'haz', 'description' => 'Családi házak'],
            ['id' => 2, 'name' => 'Lakás', 'slug' => 'lakas', 'description' => 'Lakások'],
            ['id' => 3, 'name' => 'Telek', 'slug' => 'telek', 'description' => 'Építési telkek'],
            ['id' => 4, 'name' => 'Garázs', 'slug' => 'garazs', 'description' => 'Garázsok'],
            ['id' => 5, 'name' => 'Nyaraló', 'slug' => 'nyaralo', 'description' => 'Nyaralók'],
            ['id' => 6, 'name' => 'Iroda', 'slug' => 'iroda', 'description' => 'Irodák'],
            ['id' => 7, 'name' => 'Üzlethelység', 'slug' => 'uzlethelyseg', 'description' => 'Üzletek, boltok'],
            ['id' => 8, 'name' => 'Ipari terület', 'slug' => 'ipari-terulet', 'description' => 'Ipari területek'],
            ['id' => 9, 'name' => 'Fejlesztési terület', 'slug' => 'fejlesztesi-terulet', 'description' => 'Fejlesztési területek'],
            ['id' => 10, 'name' => 'Vendéglő', 'slug' => 'vendeglo', 'description' => 'Vendéglők, éttermek'],
            ['id' => 11, 'name' => 'Mezőgazdasági terület', 'slug' => 'mezogazdasagi-terulet', 'description' => 'Mezőgazdasági területek'],
        ];

        foreach ($categories as $categoryData) {
            $category = Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                [
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                ]
            );

            $this->categoryMapping[$categoryData['id']] = $category->id;
        }

        $this->info('Imported ' . count($categories) . ' categories');
    }

    private function generateNameFromDescription(string $description): string
    {
        // Extract first 50 characters and make it a reasonable name
        $name = Str::limit($description, 50);

        // Clean up the name
        $name = preg_replace('/[^\p{L}\p{N}\s\-\.]/u', '', $name);
        $name = trim($name);

        if (empty($name)) {
            $name = 'Ingatlan';
        }

        return $name;
    }
}