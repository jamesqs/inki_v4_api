<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Modules\Locations\Models\Location;
use App\Modules\Categories\Models\Category;
use App\Modules\Estates\Models\Estate;

class MigrateOldDatabase extends Command
{
    protected $signature = 'migrate:old-db
                            {--db= : Old database name (default: inki_stage)}
                            {--dry-run : Run without making changes}';

    protected $description = 'Migrate data from old inki_stage database to new structure';

    private $dryRun = false;
    private $locationMap = [];
    private $categoryMap = [];
    private $stats = [
        'locations' => ['imported' => 0, 'skipped' => 0],
        'categories' => ['imported' => 0, 'skipped' => 0],
        'estates' => ['imported' => 0, 'skipped' => 0],
        'attributes' => ['imported' => 0],
    ];

    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        $oldDb = $this->option('db') ?? 'inki_stage';

        if ($this->dryRun) {
            $this->warn('🔄 DRY RUN MODE - No changes will be made');
        }

        $this->info("🚀 Starting migration from {$oldDb}...\n");

        try {
            // Test old database connection
            $this->testOldDbConnection($oldDb);

            // Step 1: Migrate Locations
            $this->info('📍 Step 1: Migrating Locations...');
            $this->migrateLocations($oldDb);

            // Step 2: Migrate Categories
            $this->info('📁 Step 2: Migrating Categories...');
            $this->migrateCategories($oldDb);

            // Step 3: Migrate Products (Active only)
            $this->info('🏠 Step 3: Migrating Active Products as Estates...');
            $this->migrateProducts($oldDb);

            // Show summary
            $this->showSummary();

        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    private function testOldDbConnection($oldDb)
    {
        try {
            $count = DB::connection('mysql')->select("SELECT COUNT(*) as count FROM {$oldDb}.product WHERE active = 1");
            $this->info("✅ Connected to old database. Found {$count[0]->count} active products.\n");
        } catch (\Exception $e) {
            throw new \Exception("Cannot connect to old database: " . $e->getMessage());
        }
    }

    private function migrateLocations($oldDb)
    {
        $oldLocations = DB::connection('mysql')->select("
            SELECT location_id, name, slug, lat, lng, promoted, active
            FROM {$oldDb}.location
            WHERE active = 1
            ORDER BY location_id
        ");

        $progressBar = $this->output->createProgressBar(count($oldLocations));
        $progressBar->start();

        foreach ($oldLocations as $oldLocation) {
            // Skip if name is empty
            if (empty($oldLocation->name)) {
                $this->stats['locations']['skipped']++;
                $progressBar->advance();
                continue;
            }

            // Check if location already exists by slug
            $existing = Location::where('slug', $oldLocation->slug)->first();

            if ($existing) {
                $this->locationMap[$oldLocation->location_id] = $existing->id;
                $this->stats['locations']['skipped']++;
                $progressBar->advance();
                continue;
            }

            if (!$this->dryRun) {
                // Since we don't have county mapping, we'll need to create a default county or skip
                // For now, let's just map to the first county or create dummy data
                $newLocation = Location::create([
                    'name' => $oldLocation->name,
                    'slug' => $oldLocation->slug,
                    'county_id' => 1, // You'll need to adjust this based on your counties table
                    'importance' => $oldLocation->promoted ? 10 : 0,
                    'type' => 'city',
                ]);

                $this->locationMap[$oldLocation->location_id] = $newLocation->id;
            } else {
                $this->locationMap[$oldLocation->location_id] = 'DRY-RUN-' . $oldLocation->location_id;
            }

            $this->stats['locations']['imported']++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
    }

    private function migrateCategories($oldDb)
    {
        $oldCategories = DB::connection('mysql')->select("
            SELECT id, name, slug, description, name_en, label_en
            FROM {$oldDb}.category
            WHERE active = 1 AND id > 0
            ORDER BY id
        ");

        $progressBar = $this->output->createProgressBar(count($oldCategories));
        $progressBar->start();

        foreach ($oldCategories as $oldCategory) {
            // Try to decode binary name
            $name = $this->decodeName($oldCategory->name);
            $slug = $oldCategory->slug ?: Str::slug($name);

            // Check if category already exists
            $existing = Category::where('slug', $slug)->first();

            if ($existing) {
                $this->categoryMap[$oldCategory->id] = $existing->id;
                $this->stats['categories']['skipped']++;
                $progressBar->advance();
                continue;
            }

            if (!$this->dryRun) {
                $newCategory = Category::create([
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $oldCategory->description,
                ]);

                $this->categoryMap[$oldCategory->id] = $newCategory->id;
            } else {
                $this->categoryMap[$oldCategory->id] = 'DRY-RUN-' . $oldCategory->id;
            }

            $this->stats['categories']['imported']++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
    }

    private function migrateProducts($oldDb)
    {
        // Get active products with sale type
        $oldProducts = DB::connection('mysql')->select("
            SELECT
                p.id,
                p.name,
                p.short_description,
                p.description,
                p.category_id,
                p.price,
                p.currency,
                p.saletype,
                p.location as location_id,
                p.area,
                p.created,
                p.postalcode,
                p.address,
                p.lat,
                p.lng,
                p.active,
                p.clientId
            FROM {$oldDb}.product p
            WHERE p.active = 1
            AND p.saletype IN ('sale', 'rent')
            ORDER BY p.id DESC
            LIMIT 500
        ");

        $this->info("Found " . count($oldProducts) . " active products to migrate\n");
        $progressBar = $this->output->createProgressBar(count($oldProducts));
        $progressBar->start();

        foreach ($oldProducts as $oldProduct) {
            // Check if we have mapped category and location
            if (!isset($this->categoryMap[$oldProduct->category_id])) {
                $this->stats['estates']['skipped']++;
                $progressBar->advance();
                continue;
            }

            if (!isset($this->locationMap[$oldProduct->location_id])) {
                $this->stats['estates']['skipped']++;
                $progressBar->advance();
                continue;
            }

            // Get product attributes
            $attributes = $this->getProductAttributes($oldDb, $oldProduct->id);

            if (!$this->dryRun) {
                // Generate slug from address or description
                $slug = $this->generateSlug($oldProduct);

                // Check if estate already exists
                if (Estate::where('slug', $slug)->exists()) {
                    $this->stats['estates']['skipped']++;
                    $progressBar->advance();
                    continue;
                }

                // Create estate
                $estate = Estate::create([
                    'name' => $oldProduct->name ?: $this->generateName($oldProduct),
                    'slug' => $slug,
                    'description' => $this->cleanDescription($oldProduct),
                    'price' => $this->convertPrice($oldProduct->price, $oldProduct->currency),
                    'location_id' => $this->locationMap[$oldProduct->location_id],
                    'district_id' => null, // Can be enhanced later
                    'category_id' => $this->categoryMap[$oldProduct->category_id],
                    'accepted' => true, // Old products were already accepted
                    'sold' => false,
                    'published' => true, // Active products should be published
                    'address' => $oldProduct->address,
                    'zip' => $oldProduct->postalcode,
                    'custom_attributes' => $attributes,
                    'created_at' => $oldProduct->created,
                    'updated_at' => now(),
                ]);

                $this->stats['attributes']['imported'] += count($attributes);
            }

            $this->stats['estates']['imported']++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
    }

    private function getProductAttributes($oldDb, $productId)
    {
        $values = DB::connection('mysql')->select("
            SELECT name, value
            FROM {$oldDb}.product_values
            WHERE product_id = ?
        ", [$productId]);

        $attributes = [];
        foreach ($values as $value) {
            // Group multiple values by name
            if (isset($attributes[$value->name])) {
                // Convert to array if not already
                if (!is_array($attributes[$value->name])) {
                    $attributes[$value->name] = [$attributes[$value->name]];
                }
                $attributes[$value->name][] = $value->value;
            } else {
                $attributes[$value->name] = $value->value;
            }
        }

        return $attributes;
    }

    private function decodeName($binaryName)
    {
        // Try to decode binary/hex encoded Hungarian names
        if (is_string($binaryName)) {
            return $binaryName;
        }
        return 'Unknown';
    }

    private function generateSlug($product)
    {
        $base = '';

        if ($product->address) {
            $base = Str::slug($product->address);
        } elseif ($product->name) {
            $base = Str::slug($product->name);
        } else {
            $base = 'property';
        }

        // Make unique by adding product ID
        return $base . '-' . $product->id;
    }

    private function generateName($product)
    {
        // Generate a name from category and location if no name exists
        $categoryName = array_search($product->category_id, $this->categoryMap);
        return "Property #{$product->id}";
    }

    private function cleanDescription($product)
    {
        $description = '';

        if ($product->description) {
            $description = $product->description;
        } elseif ($product->short_description) {
            $description = $product->short_description;
        } else {
            $description = 'No description available';
        }

        // Clean up any weird characters
        return trim($description);
    }

    private function convertPrice($price, $currency)
    {
        // If currency is EUR, convert to HUF (approximate rate)
        if ($currency === 'eur') {
            return $price * 400; // Approximate conversion
        }
        return $price;
    }

    private function showSummary()
    {
        $this->newLine();
        $this->info('✅ Migration completed!');
        $this->newLine();

        $this->table(
            ['Resource', 'Imported', 'Skipped'],
            [
                ['Locations', $this->stats['locations']['imported'], $this->stats['locations']['skipped']],
                ['Categories', $this->stats['categories']['imported'], $this->stats['categories']['skipped']],
                ['Estates', $this->stats['estates']['imported'], $this->stats['estates']['skipped']],
                ['Attributes', $this->stats['attributes']['imported'], '-'],
            ]
        );

        if ($this->dryRun) {
            $this->newLine();
            $this->warn('⚠️  This was a DRY RUN. No changes were made to the database.');
            $this->info('Run without --dry-run to actually import the data.');
        }
    }
}