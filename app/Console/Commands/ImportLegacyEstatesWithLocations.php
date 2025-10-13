<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Modules\Estates\Models\Estate;
use App\Modules\Categories\Models\Category;
use App\Modules\Locations\Models\Location;

class ImportLegacyEstatesWithLocations extends Command
{
    protected $signature = 'import:legacy-estates-with-locations {--file=inki_stage.sql} {--limit=0} {--only-active} {--clear-first}';
    protected $description = 'Import legacy estates with proper location mapping from inki_stage.sql';

    private array $categoryMapping = [];
    private array $locationMapping = [];

    public function handle(): int
    {
        $filePath = base_path($this->option('file'));
        $limit = (int) $this->option('limit');
        $onlyActive = $this->option('only-active');
        $clearFirst = $this->option('clear-first');

        if (!file_exists($filePath)) {
            $this->error("SQL file not found: {$filePath}");
            return 1;
        }

        $this->info('Starting enhanced legacy estates import with proper location mapping...');

        if ($clearFirst) {
            $this->info('Clearing existing estates...');
            Estate::truncate();
        }

        // Build mappings
        $this->buildCategoryMapping();
        $this->buildLocationMapping();

        // Process the file line by line
        $this->info('Processing estates...');
        $imported = $this->processEstatesFromFile($filePath, $limit, $onlyActive);

        $this->info("Import completed! Imported {$imported} estates total.");
        return 0;
    }

    private function buildCategoryMapping(): void
    {
        $legacyCategoryMapping = [
            1 => 'haz',      // Ház
            2 => 'lakas',    // Lakás
            3 => 'telek',    // Telek
            4 => 'garazs',   // Garázs
            5 => 'nyaralo',  // Nyaraló
            6 => 'iroda',    // Iroda
            7 => 'uzlethelyseg', // Üzlethelység
            8 => 'ipari-terulet', // Ipari terület
            9 => 'fejlesztesi-terulet', // Fejlesztési terület
            10 => 'vendeglo', // Vendéglő
            11 => 'mezogazdasagi-terulet', // Mezőgazdasági terület
        ];

        foreach ($legacyCategoryMapping as $legacyId => $slug) {
            $category = Category::where('slug', $slug)->first();
            if ($category) {
                $this->categoryMapping[$legacyId] = $category->id;
            }
        }

        $this->info('Built mapping for ' . count($this->categoryMapping) . ' categories');
    }

    private function buildLocationMapping(): void
    {
        // First, get legacy location mappings from the SQL file
        $filePath = base_path($this->option('file'));
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            $this->error('Could not open SQL file for location mapping');
            return;
        }

        $legacyLocations = [];
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);

            if (str_starts_with($line, 'INSERT INTO `location`')) {
                if (preg_match('/VALUES\s*\((.*)\);?$/', $line, $matches)) {
                    $fields = $this->parseLocationValues($matches[1]);
                    if (count($fields) >= 2) {
                        $legacyId = (int) $this->cleanValue($fields[0]);
                        $name = $this->cleanValue($fields[1]);
                        $active = isset($fields[3]) ? (int) $this->cleanValue($fields[3]) : 1;

                        if ($active == 1 && !empty($name)) {
                            $legacyLocations[$legacyId] = $name;
                        }
                    }
                }
            }
        }

        fclose($handle);

        // Now map to new location IDs
        foreach ($legacyLocations as $legacyId => $name) {
            $location = Location::where('name', $name)->first();
            if ($location) {
                $this->locationMapping[$legacyId] = $location->id;
            }
        }

        $this->info('Built mapping for ' . count($this->locationMapping) . ' locations');
    }

    private function parseLocationValues(string $values): array
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
                if ($i + 1 < strlen($values) && $values[$i + 1] === $quoteChar) {
                    $current .= $char;
                    $i++;
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

        $fields[] = trim($current);
        return $fields;
    }

    private function processEstatesFromFile(string $filePath, int $limit, bool $onlyActive): int
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
                if ($this->processProductLine($line, $onlyActive)) {
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
                if ($errors <= 10) {
                    $this->error("Error on line {$lineNumber}: " . $e->getMessage());
                }
            }
        }

        fclose($handle);

        $this->info("Final results: {$imported} imported, {$skipped} skipped, {$errors} errors");
        return $imported;
    }

    private function processProductLine(string $line, bool $onlyActive): bool
    {
        // Extract VALUES content using regex
        if (!preg_match('/VALUES\s*\((.*)\);?$/', $line, $matches)) {
            return false;
        }

        $values = $matches[1];

        // Parse the values
        $fields = $this->parseProductValues($values);

        if (count($fields) < 30) {
            return false;
        }

        // Extract data from fields
        $productData = [
            'id' => (int) $this->cleanValue($fields[0]),
            'name' => $this->cleanValue($fields[1]),
            'description' => $this->cleanValue($fields[5]),
            'category_id' => $this->cleanValue($fields[7]),
            'price' => $this->cleanValue($fields[9]),
            'currency' => $this->cleanValue($fields[10]) ?: 'huf',
            'saletype' => $this->cleanValue($fields[12]),
            'location' => $this->cleanValue($fields[13]), // Legacy location ID
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

        // Handle very long descriptions
        $description = $productData['description'];
        if (strlen($description) > 50000) {
            $description = substr($description, 0, 50000) . '...';
        }

        // Map category
        $categoryId = $this->categoryMapping[$productData['category_id']] ?? null;
        if (!$categoryId) {
            return false;
        }

        // Map location - use proper mapping or fallback to first location
        $locationId = null;
        if (!empty($productData['location'])) {
            $locationId = $this->locationMapping[$productData['location']] ?? null;
        }

        if (!$locationId) {
            $locationId = Location::first()?->id;
            if (!$locationId) {
                return false; // No locations available
            }
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
            'legacy_location_id' => $productData['location'],
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
            'location_id' => $locationId,
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
                if ($i + 1 < strlen($values) && $values[$i + 1] === $quoteChar) {
                    $current .= $char;
                    $i++;
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

        $fields[] = trim($current);
        return $fields;
    }

    private function cleanValue(?string $value): ?string
    {
        if ($value === null || strtoupper($value) === 'NULL' || $value === '') {
            return null;
        }

        return trim($value, "'\"");
    }

    private function generateNameFromDescription(string $description): string
    {
        $name = Str::limit($description, 50);
        $name = preg_replace('/[^\p{L}\p{N}\s\-\.]/u', '', $name);
        $name = trim($name);

        if (empty($name)) {
            $name = 'Ingatlan';
        }

        return $name;
    }
}