<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrateAttributes extends Command
{
    protected $signature = 'migrate:attributes
                            {--db= : Old database name (default: inki_stage)}
                            {--dry-run : Run without making changes}';

    protected $description = 'Migrate form_fields from old database to attributes table and link to categories';

    private $dryRun = false;
    private $categoryMap = [];
    private $stats = [
        'attributes' => ['created' => 0, 'skipped' => 0],
        'relationships' => ['created' => 0, 'skipped' => 0],
    ];

    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        $oldDb = $this->option('db') ?? 'inki_stage';

        if ($this->dryRun) {
            $this->warn('🔄 DRY RUN MODE - No changes will be made');
        }

        $this->info("🚀 Starting attribute migration from {$oldDb}...\n");

        try {
            // Step 1: Build category map
            $this->info('📁 Step 1: Building category map...');
            $this->buildCategoryMap($oldDb);

            // Step 2: Migrate form fields to attributes
            $this->info('🏷️  Step 2: Migrating form fields to attributes...');
            $this->migrateFormFields($oldDb);

            // Show summary
            $this->showSummary();

        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    private function buildCategoryMap($oldDb)
    {
        // Get old categories
        $oldCategories = DB::connection('mysql')->select("
            SELECT id, slug
            FROM {$oldDb}.category
            WHERE active = 1 AND id > 0
        ");

        // Get new categories
        $newCategories = DB::table('categories')->get(['id', 'slug']);

        foreach ($oldCategories as $oldCat) {
            $newCat = $newCategories->firstWhere('slug', $oldCat->slug);
            if ($newCat) {
                $this->categoryMap[$oldCat->id] = $newCat->id;
            }
        }

        $this->info("  Mapped " . count($this->categoryMap) . " categories\n");
    }

    private function migrateFormFields($oldDb)
    {
        // Get all form fields
        $formFields = DB::connection('mysql')->select("
            SELECT
                ff.id,
                ff.form_id,
                ff.name as display_name,
                ff.type,
                ff.values,
                ff.title as slug
            FROM {$oldDb}.form_fields ff
            ORDER BY ff.form_id, ff.id
        ");

        $this->info("  Found " . count($formFields) . " form fields\n");

        $progressBar = $this->output->createProgressBar(count($formFields));
        $progressBar->start();

        foreach ($formFields as $field) {
            // Check if attribute already exists
            $existingAttribute = DB::table('attributes')
                ->where('slug', $field->slug)
                ->first();

            if ($existingAttribute) {
                $attributeId = $existingAttribute->id;
                $this->stats['attributes']['skipped']++;
            } else {
                // Create new attribute
                if (!$this->dryRun) {
                    $attributeId = DB::table('attributes')->insertGetId([
                        'name' => $field->display_name,
                        'slug' => $field->slug,
                        'type' => $this->mapFieldType($field->type),
                        'options' => $this->parseOptions($field->values),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $attributeId = 'DRY-RUN-' . $field->id;
                }
                $this->stats['attributes']['created']++;
            }

            // Get categories for this form
            $formCategories = DB::connection('mysql')->select("
                SELECT category_id
                FROM {$oldDb}.form_categories
                WHERE form_id = ?
            ", [$field->form_id]);

            // Link attribute to categories
            foreach ($formCategories as $formCat) {
                if (!isset($this->categoryMap[$formCat->category_id])) {
                    continue;
                }

                $newCategoryId = $this->categoryMap[$formCat->category_id];

                // Check if relationship already exists
                if (!$this->dryRun) {
                    $exists = DB::table('attribute_category')
                        ->where('attribute_id', $attributeId)
                        ->where('category_id', $newCategoryId)
                        ->exists();

                    if (!$exists) {
                        DB::table('attribute_category')->insert([
                            'attribute_id' => $attributeId,
                            'category_id' => $newCategoryId,
                            'required' => false,
                            'order' => $field->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $this->stats['relationships']['created']++;
                    } else {
                        $this->stats['relationships']['skipped']++;
                    }
                } else {
                    $this->stats['relationships']['created']++;
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
    }

    private function mapFieldType($oldType)
    {
        return match($oldType) {
            'text' => 'text',
            'textarea' => 'textarea',
            'select' => 'select',
            'multipleselect' => 'multiselect',
            'checkbox' => 'multiselect',
            'type' => 'text',
            default => 'text',
        };
    }

    private function parseOptions($values)
    {
        if (empty($values)) {
            return null;
        }

        // Split by comma
        $options = explode(',', $values);
        $options = array_map('trim', $options);

        return json_encode($options);
    }

    private function showSummary()
    {
        $this->newLine();
        $this->info('✅ Attribute migration completed!');
        $this->newLine();

        $this->table(
            ['Resource', 'Created', 'Skipped'],
            [
                ['Attributes', $this->stats['attributes']['created'], $this->stats['attributes']['skipped']],
                ['Category Links', $this->stats['relationships']['created'], $this->stats['relationships']['skipped']],
            ]
        );

        if ($this->dryRun) {
            $this->newLine();
            $this->warn('⚠️  This was a DRY RUN. No changes were made to the database.');
            $this->info('Run without --dry-run to actually import the data.');
        }
    }
}