<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Modules\Estates\Models\Estate;
use App\Modules\Categories\Models\Category;
use App\Modules\Locations\Models\Location;

class ImportLegacyData extends Command
{
    protected $signature = 'import:legacy-data {--file=inki_stage.sql}';
    protected $description = 'Import legacy data from inki_stage.sql dump file';

    private array $categoryMapping = [];
    private array $productValues = [];

    public function handle(): int
    {
        $filePath = base_path($this->option('file'));

        if (!file_exists($filePath)) {
            $this->error("SQL file not found: {$filePath}");
            return 1;
        }

        $this->info('Starting legacy data import...');

        // Parse the SQL file
        $this->info('Parsing SQL file...');
        $this->parseSqlFile($filePath);

        // Import categories first
        $this->info('Importing categories...');
        $this->importCategories();

        // Import estates
        $this->info('Importing estates...');
        $this->importEstates();

        $this->info('Legacy data import completed successfully!');
        return 0;
    }

    private function parseSqlFile(string $filePath): void
    {
        $content = file_get_contents($filePath);

        // Extract product_values data
        $this->parseProductValues($content);

        // Extract product data
        $this->parseProducts($content);
    }

    private array $products = [];

    private function parseProductValues(string $content): void
    {
        $this->info('Parsing product values...');

        // Find all product_values INSERT statements
        preg_match_all('/INSERT INTO `product_values`[^;]*VALUES\s*([^;]+);/s', $content, $matches);

        $totalValues = 0;
        foreach ($matches[1] as $valueSet) {
            // Parse individual value rows
            preg_match_all("/\('([^']*)',\s*'([^']*)',\s*(\d+),\s*\d+\)/", $valueSet, $rowMatches, PREG_SET_ORDER);

            foreach ($rowMatches as $match) {
                $name = $match[1];
                $value = $match[2];
                $productId = (int)$match[3];

                if (!isset($this->productValues[$productId])) {
                    $this->productValues[$productId] = [];
                }

                $this->productValues[$productId][$name] = $value;
                $totalValues++;
            }
        }

        $this->info("Parsed {$totalValues} product values");
    }

    private function parseProducts(string $content): void
    {
        $this->info('Parsing products...');

        // Find all product INSERT statements
        preg_match_all('/INSERT INTO `product`[^;]*VALUES\s*([^;]+);/s', $content, $matches);

        $totalProducts = 0;
        foreach ($matches[1] as $valueSet) {
            // Split into individual product rows - this is complex due to nested quotes and nulls
            $this->parseProductRows($valueSet);
            $totalProducts += count($this->products);
        }

        $this->info("Parsed {$totalProducts} products");
    }

    private function parseProductRows(string $valueSet): void
    {
        // This regex pattern handles the complex product INSERT format
        $pattern = '/\((\d+),\s*(?:\'([^\']*)\'),?\s*(?:\'([^\']*)\')?,\s*(?:\'([^\']*)\')?,\s*(?:\'([^\']*)\')?,\s*(?:\'([^\']*)\')?,\s*(?:\'([^\']*)\')?,\s*(\d+),\s*(?:(\d+))?,\s*(?:(\d+))?,\s*\'([^\']*)\',\s*(\d+),\s*\'([^\']*)\',\s*(?:(\d+))?,\s*(?:(\d+))?,\s*(?:\'([^\']*)\')?,\s*(?:\'([^\']*)\')?,\s*(?:\'([^\']*)\')?,\s*\'([^\']*)\',\s*(?:(\d+))?,\s*(?:(\d+))?,\s*(?:\'([^\']*)\')?,\s*(?:([^,\)]*))?,\s*(?:([^,\)]*))?,\s*(?:\'([^\']*)\')?,\s*(?:(\d+))?,\s*(?:(\d+))?,\s*(\d+),\s*(\d+),\s*(?:\'([^\']*)\')?,\s*(?:(\d+))?,\s*(?:0x[^,\)]*),\s*(?:0x[^,\)]*),\s*(?:(\d+))?\)/';

        // Simpler approach: split by '),(' and parse each row
        $rows = explode('),(', trim($valueSet, '()'));

        foreach ($rows as $row) {
            $this->parseProductRow('(' . $row . ')');
        }
    }

    private function parseProductRow(string $row): void
    {
        // Remove outer parentheses
        $row = trim($row, '()');

        // Split by comma but be careful with quoted strings
        $values = $this->splitCsvRow($row);

        if (count($values) >= 20) { // Ensure we have minimum required fields
            $product = [
                'id' => (int)$values[0],
                'name' => $this->cleanValue($values[1]),
                'name_en' => $this->cleanValue($values[2]),
                'short_description' => $this->cleanValue($values[3]),
                'short_description_en' => $this->cleanValue($values[4]),
                'description' => $this->cleanValue($values[5]),
                'description_en' => $this->cleanValue($values[6]),
                'category_id' => isset($values[7]) ? (int)$values[7] : null,
                'brand' => isset($values[8]) ? (int)$values[8] : null,
                'price' => isset($values[9]) ? (int)$values[9] : null,
                'currency' => $this->cleanValue($values[10] ?? 'huf'),
                'highlight' => isset($values[11]) ? (int)$values[11] : 0,
                'saletype' => $this->cleanValue($values[12]),
                'location' => isset($values[13]) ? (int)$values[13] : null,
                'area' => isset($values[14]) ? (int)$values[14] : 0,
                'owner_name' => $this->cleanValue($values[15]),
                'owner_email' => $this->cleanValue($values[16]),
                'owner_phone' => $this->cleanValue($values[17]),
                'created' => $this->cleanValue($values[18]),
                'createdby' => isset($values[19]) ? (int)$values[19] : null,
                'postalcode' => isset($values[20]) ? $values[20] : null,
                'address' => $this->cleanValue($values[21]),
                'active' => isset($values[27]) ? (int)$values[27] : 0,
            ];

            $this->products[] = $product;
        }
    }

    private function splitCsvRow(string $row): array
    {
        $values = [];
        $current = '';
        $inQuotes = false;
        $i = 0;

        while ($i < strlen($row)) {
            $char = $row[$i];

            if ($char === "'" && !$inQuotes) {
                $inQuotes = true;
            } elseif ($char === "'" && $inQuotes) {
                // Check if it's an escaped quote
                if ($i + 1 < strlen($row) && $row[$i + 1] === "'") {
                    $current .= "'";
                    $i++; // Skip next quote
                } else {
                    $inQuotes = false;
                }
            } elseif ($char === ',' && !$inQuotes) {
                $values[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
            $i++;
        }

        // Add the last value
        $values[] = trim($current);

        return $values;
    }

    private function cleanValue(?string $value): ?string
    {
        if ($value === null || $value === 'NULL' || $value === '') {
            return null;
        }

        // Remove surrounding quotes
        return trim($value, "'\"");
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

    private function importEstates(): void
    {
        $defaultLocation = Location::first();
        if (!$defaultLocation) {
            $this->error('No locations found. Please ensure locations are seeded first.');
            return;
        }

        $this->info('Importing ' . count($this->products) . ' products as estates...');

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($this->products as $productData) {
            // Skip inactive products if desired
            if ($productData['active'] == 0) {
                $skipped++;
                continue;
            }

            try {
                if ($this->importSingleEstate($productData, $defaultLocation)) {
                    $imported++;

                    // Show progress every 100 imports
                    if ($imported % 100 == 0) {
                        $this->info("Imported {$imported} estates so far...");
                    }
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("Error importing product {$productData['id']}: " . $e->getMessage());
            }
        }

        $this->info("Import completed: {$imported} imported, {$skipped} skipped, {$errors} errors");
    }

    private function importSingleEstate(array $estateData, Location $defaultLocation): bool
    {
        // Map category
        $categoryId = $this->categoryMapping[$estateData['category_id']] ?? null;
        if (!$categoryId) {
            return false; // Skip if category not found
        }

        // Skip if no description (main content)
        if (empty($estateData['description'])) {
            return false;
        }

        // Generate name and slug
        $name = $estateData['name'] ?: $this->generateNameFromDescription($estateData['description']);
        $slug = Str::slug($name) . '-' . $estateData['id'];

        // Make sure slug is unique
        $originalSlug = $slug;
        $counter = 1;
        while (Estate::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Build custom attributes
        $customAttributes = [
            'area' => $estateData['area'] ?? 0,
            'currency' => $estateData['currency'] ?? 'huf',
            'sale_type' => $estateData['saletype'] ?? 'sale',
            'original_id' => $estateData['id'],
        ];

        // Add owner information if available
        if (!empty($estateData['owner_name'])) {
            $customAttributes['owner_name'] = $estateData['owner_name'];
        }
        if (!empty($estateData['owner_email'])) {
            $customAttributes['owner_email'] = $estateData['owner_email'];
        }
        if (!empty($estateData['owner_phone'])) {
            $customAttributes['owner_phone'] = $estateData['owner_phone'];
        }

        // Add product_values if available
        if (isset($this->productValues[$estateData['id']])) {
            $customAttributes = array_merge($customAttributes, $this->productValues[$estateData['id']]);
        }

        // Handle price (could be null)
        $price = !empty($estateData['price']) ? $estateData['price'] : null;

        // Create estate
        Estate::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $estateData['description'],
            'price' => $price,
            'location_id' => $defaultLocation->id,
            'category_id' => $categoryId,
            'accepted' => true,
            'sold' => false,
            'published' => $estateData['active'] == 1,
            'address' => $estateData['address'],
            'zip' => $estateData['postalcode'],
            'custom_attributes' => $customAttributes,
            'created_at' => $estateData['created'] ?? now(),
            'updated_at' => now(),
        ]);

        return true;
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