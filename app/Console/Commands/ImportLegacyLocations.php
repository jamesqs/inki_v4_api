<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Modules\Locations\Models\Location;
use App\Modules\Counties\Models\County;

class ImportLegacyLocations extends Command
{
    protected $signature = 'import:legacy-locations {--file=inki_stage.sql}';
    protected $description = 'Import legacy location data from inki_stage.sql';

    public function handle(): int
    {
        $filePath = base_path($this->option('file'));

        if (!file_exists($filePath)) {
            $this->error("SQL file not found: {$filePath}");
            return 1;
        }

        $this->info('Starting legacy locations import...');

        // Get default county (or create one if none exists)
        $defaultCounty = County::first();
        if (!$defaultCounty) {
            $this->warn('No counties found. Creating default county...');
            $defaultCounty = County::create([
                'name' => 'Baranya',
                'slug' => 'baranya',
                'code' => 'BA',
            ]);
        }

        // Process the file line by line
        $imported = $this->processLocationsFromFile($filePath, $defaultCounty);

        $this->info("Import completed! Imported {$imported} locations total.");
        return 0;
    }

    private function processLocationsFromFile(string $filePath, County $defaultCounty): int
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

            // Skip non-location lines
            if (!str_starts_with($line, 'INSERT INTO `location`')) {
                continue;
            }

            try {
                if ($this->processLocationLine($line, $defaultCounty)) {
                    $imported++;

                    // Show progress every 100 imports
                    if ($imported % 100 == 0) {
                        $this->info("Imported {$imported} locations so far... (line {$lineNumber})");
                    }
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors++;
                if ($errors <= 5) { // Only show first 5 errors
                    $this->error("Error on line {$lineNumber}: " . $e->getMessage());
                }
            }
        }

        fclose($handle);

        $this->info("Final results: {$imported} imported, {$skipped} skipped, {$errors} errors");
        return $imported;
    }

    private function processLocationLine(string $line, County $defaultCounty): bool
    {
        // Extract VALUES content using regex
        if (!preg_match('/VALUES\s*\((.*)\);?$/', $line, $matches)) {
            return false;
        }

        $values = $matches[1];

        // Parse the values
        $fields = $this->parseLocationValues($values);

        if (count($fields) < 4) { // Ensure we have minimum required fields
            return false;
        }

        // Extract data from fields based on CREATE TABLE structure
        $locationData = [
            'location_id' => (int) $this->cleanValue($fields[0]),
            'name' => $this->cleanValue($fields[1]),
            'updated' => $this->cleanValue($fields[2]),
            'active' => (int) $this->cleanValue($fields[3]),
            'lat' => $this->cleanValue($fields[4]),
            'lng' => $this->cleanValue($fields[5]),
            'promoted' => isset($fields[6]) ? (int) $this->cleanValue($fields[6]) : 0,
            'slug' => $this->cleanValue($fields[7]),
        ];

        // Skip if no name
        if (empty($locationData['name'])) {
            return false;
        }

        // Skip inactive locations
        if ($locationData['active'] != 1) {
            return false;
        }

        // Generate slug if not present
        $slug = $locationData['slug'] ?: Str::slug($locationData['name']);

        // Make sure slug is unique
        $originalSlug = $slug;
        $counter = 1;
        while (Location::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Determine location type based on name patterns
        $type = $this->determineLocationType($locationData['name']);

        // Create location
        Location::create([
            'name' => $locationData['name'],
            'slug' => $slug,
            'importance' => $locationData['promoted'] ?: 0,
            'county_id' => $defaultCounty->id,
            'type' => $type,
            'has_districts' => $this->hasDistricts($locationData['name']),
            'has_sub_districts' => false,
        ]);

        return true;
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

    private function determineLocationType(string $name): string
    {
        // Convert to lowercase for pattern matching
        $name = strtolower($name);

        // Hungarian city/town type patterns
        if (in_array($name, ['budapest', 'debrecen', 'szeged', 'miskolc', 'pécs', 'győr', 'nyíregyháza', 'kecskemét'])) {
            return 'city';
        }

        // Common town suffixes in Hungarian
        if (str_ends_with($name, 'város') || str_ends_with($name, 'város')) {
            return 'city';
        }

        if (str_ends_with($name, 'háza') || str_ends_with($name, 'haza')) {
            return 'village';
        }

        if (str_ends_with($name, 'telep') || str_ends_with($name, 'major')) {
            return 'hamlet';
        }

        // Default to town
        return 'town';
    }

    private function hasDistricts(string $name): bool
    {
        // Major Hungarian cities that typically have districts
        $citiesWithDistricts = [
            'budapest', 'debrecen', 'szeged', 'miskolc', 'pécs',
            'győr', 'nyíregyháza', 'kecskemét', 'székesfehérvár', 'szombathely'
        ];

        return in_array(strtolower($name), $citiesWithDistricts);
    }
}