<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Modules\Estates\Models\Estate;
use App\Modules\Categories\Models\Category;
use App\Modules\Locations\Models\Location;

class ImportLegacyDataFixed extends Command
{
    protected $signature = 'import:legacy-data-fixed {--file=inki_stage.sql} {--limit=0}';
    protected $description = 'Import ALL legacy data from inki_stage.sql dump file (fixed version)';

    private array $categoryMapping = [];
    private array $productValues = [];

    public function handle(): int
    {
        $filePath = base_path($this->option('file'));
        $limit = (int) $this->option('limit');

        if (!file_exists($filePath)) {
            $this->error("SQL file not found: {$filePath}");
            return 1;
        }

        $this->info('Starting legacy data import (fixed version)...');

        // Import categories first
        $this->info('Importing categories...');
        $this->importCategories();

        // Parse and import using a more reliable method
        $this->info('Parsing and importing products...');
        $this->importProductsFromSqlFile($filePath, $limit);

        $this->info('Legacy data import completed successfully!');
        return 0;
    }

    private function importProductsFromSqlFile(string $filePath, int $limit = 0): void
    {
        $defaultLocation = Location::first();
        if (!$defaultLocation) {
            $this->error('No locations found. Please ensure locations are seeded first.');
            return;
        }

        // Read file line by line to avoid memory issues
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error('Could not open SQL file');
            return;
        }

        $imported = 0;
        $skipped = 0;
        $errors = 0;
        $inProductInsert = false;
        $currentInsert = '';

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);

            // Look for product INSERT statements
            if (preg_match('/^INSERT INTO `product`/i', $line)) {
                $inProductInsert = true;
                $currentInsert = $line;
                continue;
            }

            if ($inProductInsert) {
                if (substr($line, -1) === ';') {
                    // End of INSERT statement
                    $currentInsert .= ' ' . $line;
                    $imported += $this->processProductInsert($currentInsert, $defaultLocation);

                    $inProductInsert = false;
                    $currentInsert = '';

                    // Show progress
                    if ($imported % 50 == 0 && $imported > 0) {
                        $this->info("Imported {$imported} estates so far...");
                    }

                    // Apply limit if set
                    if ($limit > 0 && $imported >= $limit) {
                        break;
                    }
                } else {
                    $currentInsert .= ' ' . $line;
                }
            }
        }

        fclose($handle);

        $this->info("Import completed: {$imported} imported");
    }

    private function processProductInsert(string $insertSql, Location $defaultLocation): int
    {
        // Extract the VALUES part
        if (!preg_match('/VALUES\s*(.+);?$/is', $insertSql, $matches)) {
            return 0;
        }

        $valuesSection = $matches[1];

        // Remove trailing semicolon if present
        $valuesSection = rtrim($valuesSection, ';');

        $imported = 0;
        $level = 0;
        $current = '';
        $inQuote = false;
        $quoteChar = '';

        for ($i = 0; $i < strlen($valuesSection); $i++) {
            $char = $valuesSection[$i];
            $current .= $char;

            if (!$inQuote && ($char === '"' || $char === "'")) {
                $inQuote = true;
                $quoteChar = $char;
            } elseif ($inQuote && $char === $quoteChar) {
                // Check if it's escaped
                if ($i + 1 < strlen($valuesSection) && $valuesSection[$i + 1] === $quoteChar) {
                    $current .= $valuesSection[$i + 1]; // Add the escaped quote
                    $i++; // Skip next character
                } else {
                    $inQuote = false;
                    $quoteChar = '';
                }
            } elseif (!$inQuote) {
                if ($char === '(') {
                    $level++;
                } elseif ($char === ')') {
                    $level--;
                    if ($level === 0) {
                        // We have a complete row
                        if ($this->processProductRow($current, $defaultLocation)) {
                            $imported++;
                        }

                        // Skip comma and whitespace after the row
                        while ($i + 1 < strlen($valuesSection) &&
                               ($valuesSection[$i + 1] === ',' || ctype_space($valuesSection[$i + 1]))) {
                            $i++;
                        }

                        $current = '';
                    }
                }
            }
        }

        return $imported;
    }

    private function processProductRow(string $row, Location $defaultLocation): bool
    {
        // Remove outer parentheses and parse the row
        $row = trim($row, '()');

        // Split by comma, being careful with quoted strings
        $fields = $this->parseFields($row);

        if (count($fields) < 20) {
            return false; // Not enough fields
        }

        try {
            $productData = [
                'id' => (int) $this->cleanField($fields[0]),
                'name' => $this->cleanField($fields[1]),
                'description' => $this->cleanField($fields[5]), // Main description field
                'category_id' => $this->cleanField($fields[7]),
                'price' => $this->cleanField($fields[9]),
                'currency' => $this->cleanField($fields[10]) ?: 'huf',
                'saletype' => $this->cleanField($fields[12]),
                'area' => $this->cleanField($fields[14]) ?: 0,
                'owner_name' => $this->cleanField($fields[15]),
                'owner_email' => $this->cleanField($fields[16]),
                'owner_phone' => $this->cleanField($fields[17]),
                'created' => $this->cleanField($fields[18]),
                'address' => $this->cleanField($fields[21]),
                'postalcode' => $this->cleanField($fields[20]),
                'active' => isset($fields[27]) ? (int) $this->cleanField($fields[27]) : 0,
            ];

            return $this->importSingleEstate($productData, $defaultLocation);
        } catch (\Exception $e) {
            // Skip problematic rows
            return false;
        }
    }

    private function parseFields(string $row): array
    {
        $fields = [];
        $current = '';
        $inQuote = false;
        $quoteChar = '';
        $i = 0;

        while ($i < strlen($row)) {
            $char = $row[$i];

            if (!$inQuote && ($char === '"' || $char === "'")) {
                $inQuote = true;
                $quoteChar = $char;
                $current .= $char;
            } elseif ($inQuote && $char === $quoteChar) {
                // Check if escaped
                if ($i + 1 < strlen($row) && $row[$i + 1] === $quoteChar) {
                    $current .= $char . $row[$i + 1];
                    $i++; // Skip next
                } else {
                    $inQuote = false;
                    $quoteChar = '';
                    $current .= $char;
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

    private function cleanField(?string $value): ?string
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