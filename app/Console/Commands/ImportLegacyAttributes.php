<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Modules\Attributes\Models\Attribute;
use App\Modules\Categories\Models\Category;

class ImportLegacyAttributes extends Command
{
    protected $signature = 'import:legacy-attributes {--file=inki_stage.sql}';
    protected $description = 'Import legacy attributes and connect them to categories from inki_stage.sql';

    private array $categoryMapping = [];

    public function handle(): int
    {
        $filePath = base_path($this->option('file'));

        if (!file_exists($filePath)) {
            $this->error("SQL file not found: {$filePath}");
            return 1;
        }

        $this->info('Starting legacy attributes import...');

        // Build category mapping
        $this->buildCategoryMapping();

        // Process the file to get forms, form_fields, and form_categories
        $imported = $this->processAttributesFromFile($filePath);

        $this->info("Import completed! Imported {$imported} attributes total.");
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

    private function processAttributesFromFile(string $filePath): int
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error('Could not open SQL file');
            return 0;
        }

        $formFields = [];
        $formCategories = [];
        $imported = 0;

        // First pass: collect form_fields and form_categories
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);

            if (str_starts_with($line, 'INSERT INTO `form_fields`')) {
                $this->parseFormFieldsLine($line, $formFields);
            } elseif (str_starts_with($line, 'INSERT INTO `form_categories`')) {
                $this->parseFormCategoriesLine($line, $formCategories);
            }
        }

        fclose($handle);

        // Second pass: create attributes and connect to categories
        $imported = $this->createAttributesAndConnections($formFields, $formCategories);

        return $imported;
    }

    private function parseFormFieldsLine(string $line, array &$formFields): void
    {
        if (!preg_match('/VALUES\s*(.+);?$/', $line, $matches)) {
            return;
        }

        $valuesSection = $matches[1];

        // Split by '),(' to get individual records
        $records = [];
        $this->parseMultipleRecords($valuesSection, $records);

        foreach ($records as $record) {
            $fields = $this->parseValues($record);

            if (count($fields) >= 6) {
                $formFields[] = [
                    'form_id' => (int) $this->cleanValue($fields[0]),
                    'id' => (int) $this->cleanValue($fields[1]),
                    'name' => $this->cleanValue($fields[2]),
                    'type' => $this->cleanValue($fields[3]),
                    'values' => $this->cleanValue($fields[4]),
                    'title' => $this->cleanValue($fields[5]),
                    'searchable' => isset($fields[6]) ? (int) $this->cleanValue($fields[6]) : 0,
                ];
            }
        }
    }

    private function parseFormCategoriesLine(string $line, array &$formCategories): void
    {
        if (!preg_match('/VALUES\s*(.+);?$/', $line, $matches)) {
            return;
        }

        $valuesSection = $matches[1];

        $records = [];
        $this->parseMultipleRecords($valuesSection, $records);

        foreach ($records as $record) {
            $fields = $this->parseValues($record);

            if (count($fields) >= 2) {
                $formCategories[] = [
                    'form_id' => (int) $this->cleanValue($fields[0]),
                    'category_id' => (int) $this->cleanValue($fields[1]),
                ];
            }
        }
    }

    private function parseMultipleRecords(string $valuesSection, array &$records): void
    {
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
                if ($i + 1 < strlen($valuesSection) && $valuesSection[$i + 1] === $quoteChar) {
                    $current .= $valuesSection[$i + 1];
                    $i++;
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
                        $records[] = trim($current, '()');

                        // Skip comma and whitespace after the record
                        while ($i + 1 < strlen($valuesSection) &&
                               ($valuesSection[$i + 1] === ',' || ctype_space($valuesSection[$i + 1]))) {
                            $i++;
                        }

                        $current = '';
                    }
                }
            }
        }
    }

    private function parseValues(string $record): array
    {
        $fields = [];
        $current = '';
        $inQuote = false;
        $quoteChar = '';
        $i = 0;

        while ($i < strlen($record)) {
            $char = $record[$i];

            if (!$inQuote && ($char === '"' || $char === "'")) {
                $inQuote = true;
                $quoteChar = $char;
                $current .= $char;
            } elseif ($inQuote && $char === $quoteChar) {
                if ($i + 1 < strlen($record) && $record[$i + 1] === $quoteChar) {
                    $current .= $char . $record[$i + 1];
                    $i++;
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

    private function createAttributesAndConnections(array $formFields, array $formCategories): int
    {
        $this->info('Processing ' . count($formFields) . ' form fields...');
        $this->info('Processing ' . count($formCategories) . ' form-category connections...');

        // Build form to categories mapping
        $formToCategoriesMap = [];
        foreach ($formCategories as $fc) {
            if (!isset($formToCategoriesMap[$fc['form_id']])) {
                $formToCategoriesMap[$fc['form_id']] = [];
            }
            $formToCategoriesMap[$fc['form_id']][] = $fc['category_id'];
        }

        $created = 0;
        $uniqueAttributes = [];

        // Create unique attributes
        foreach ($formFields as $field) {
            $attributeKey = $field['title']; // Use title as unique key

            if (!isset($uniqueAttributes[$attributeKey])) {
                $uniqueAttributes[$attributeKey] = [
                    'name' => $field['name'],
                    'title' => $field['title'],
                    'type' => $this->mapFieldType($field['type']),
                    'options' => $this->parseFieldOptions($field['values']),
                    'forms' => [],
                ];
            }

            // Add form association
            $uniqueAttributes[$attributeKey]['forms'][] = $field['form_id'];
        }

        // Create attributes and connect to categories
        foreach ($uniqueAttributes as $attrKey => $attrData) {
            // Create the attribute
            $attribute = Attribute::create([
                'name' => $attrData['name'],
                'slug' => Str::slug($attrData['title']),
                'description' => null,
                'type' => $attrData['type'],
                'options' => $attrData['options'],
            ]);

            $created++;

            // Connect to categories through forms
            $connectedCategories = [];
            foreach (array_unique($attrData['forms']) as $formId) {
                if (isset($formToCategoriesMap[$formId])) {
                    foreach ($formToCategoriesMap[$formId] as $legacyCategoryId) {
                        if (isset($this->categoryMapping[$legacyCategoryId])) {
                            $categoryId = $this->categoryMapping[$legacyCategoryId];
                            if (!in_array($categoryId, $connectedCategories)) {
                                $connectedCategories[] = $categoryId;
                            }
                        }
                    }
                }
            }

            // Attach to categories
            foreach ($connectedCategories as $categoryId) {
                $attribute->categories()->attach($categoryId, [
                    'required' => $this->isAttributeRequired($attrData['title']),
                    'order' => $this->getAttributeOrder($attrData['title']),
                ]);
            }

            if ($created % 10 == 0) {
                $this->info("Created {$created} attributes so far...");
            }
        }

        return $created;
    }

    private function mapFieldType(string $type): string
    {
        return match($type) {
            'text' => 'text',
            'textarea' => 'textarea',
            'select' => 'select',
            'multipleselect' => 'multiselect',
            'checkbox' => 'multiselect',
            default => 'text',
        };
    }

    private function parseFieldOptions(?string $values): ?array
    {
        if (empty($values)) {
            return null;
        }

        $options = explode(',', $values);
        return array_map('trim', $options);
    }

    private function isAttributeRequired(string $title): bool
    {
        // Define which attributes should be required
        $requiredAttributes = [
            'szobak_szama',
            'felszobak_szama',
            'komfort_szintje',
            'futes_tipusa',
        ];

        return in_array($title, $requiredAttributes);
    }

    private function getAttributeOrder(string $title): int
    {
        // Define order for common attributes
        $attributeOrder = [
            'szobak_szama' => 1,
            'felszobak_szama' => 2,
            'komfort_szintje' => 3,
            'futes_tipusa' => 4,
            'emelet' => 5,
            'telek_alapterulet' => 6,
            'extrak' => 100, // Last
        ];

        return $attributeOrder[$title] ?? 50; // Default order
    }
}