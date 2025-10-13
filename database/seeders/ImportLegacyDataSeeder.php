<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Modules\Estates\Models\Estate;
use App\Modules\Categories\Models\Category;
use App\Modules\Locations\Models\Location;

class ImportLegacyDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Importing legacy data from inki_stage.sql...');

        // First, import categories
        $this->importCategories();

        // Then import estates/products
        $this->importEstates();

        $this->command->info('Legacy data import completed!');
    }

    private function importCategories(): void
    {
        $this->command->info('Importing categories...');

        // Get legacy categories from the SQL file content
        $legacyCategories = [
            ['id' => 0, 'name' => 'Ingatlanok', 'slug' => 'Ingatlanok', 'name_en' => 'Estates'],
            ['id' => 1, 'name' => 'ház', 'slug' => 'haz', 'name_en' => 'house'],
            ['id' => 2, 'name' => 'lakás', 'slug' => 'lakas', 'name_en' => 'flat'],
            ['id' => 3, 'name' => 'telek', 'slug' => 'telek', 'name_en' => 'site'],
            ['id' => 4, 'name' => 'garázs', 'slug' => 'garazs', 'name_en' => 'garage'],
            ['id' => 5, 'name' => 'nyaraló', 'slug' => 'nyaralo', 'name_en' => 'summer house'],
            ['id' => 6, 'name' => 'iroda', 'slug' => 'iroda', 'name_en' => 'office'],
            ['id' => 7, 'name' => 'üzlethelység', 'slug' => 'uzlethelyseg', 'name_en' => 'premises'],
            ['id' => 8, 'name' => 'ipari terület', 'slug' => 'ipari_fejlesztesiterulet', 'name_en' => 'industrial area'],
            ['id' => 9, 'name' => 'fejlesztési terület', 'slug' => 'fejlesztesiterulet', 'name_en' => 'development area'],
            ['id' => 10, 'name' => 'vendéglő, étterem', 'slug' => 'vendeglo', 'name_en' => 'inn'],
            ['id' => 11, 'name' => 'mezőgazdasági', 'slug' => 'mezogazdasagi', 'name_en' => 'agricultural area'],
        ];

        foreach ($legacyCategories as $legacyCategory) {
            if ($legacyCategory['id'] == 0) continue; // Skip root category

            Category::updateOrCreate(
                ['slug' => $legacyCategory['slug']],
                [
                    'name' => $legacyCategory['name'],
                    'description' => null,
                ]
            );
        }

        $this->command->info('Categories imported successfully!');
    }

    private function importEstates(): void
    {
        $this->command->info('Importing estates (products)...');

        // Connect to the legacy database or read from SQL file
        // For this example, we'll assume you've imported the SQL dump to a temporary database
        // or you can parse the SQL file directly

        // You would need to either:
        // 1. Import the SQL dump to a temporary database and query it
        // 2. Parse the SQL INSERT statements from the file
        // 3. Have the data available in another format

        // Example using temporary database connection:
        $this->importEstatesFromTempDatabase();
    }

    private function importEstatesFromTempDatabase(): void
    {
        // This assumes you've imported the SQL dump to a database called 'inki_stage_temp'
        $tempConnection = config('database.connections.mysql');
        $tempConnection['database'] = 'inki_stage_temp';
        config(['database.connections.temp' => $tempConnection]);

        try {
            $legacyProducts = DB::connection('temp')->table('product')
                ->where('active', '!=', 0) // Only import active products
                ->get();

            foreach ($legacyProducts as $product) {
                $this->importSingleEstate($product);
            }
        } catch (\Exception $e) {
            $this->command->error('Could not connect to temporary database. Please ensure you have imported the SQL dump to a database called "inki_stage_temp"');
            $this->command->error('Error: ' . $e->getMessage());

            // Alternative: Import from predefined sample data
            $this->importSampleEstates();
        }
    }

    private function importSingleEstate($product): void
    {
        // Map legacy category ID to new category
        $category = Category::where('id', $this->mapCategoryId($product->category_id))->first();
        if (!$category) {
            $category = Category::first(); // Fallback to first available category
        }

        // Create a default location if needed (you'll need to implement location mapping)
        $location = Location::first(); // Fallback to first available location

        // Generate slug from name or description
        $name = $product->name ?: 'Estate ' . $product->id;
        $slug = Str::slug($name) . '-' . $product->id;

        // Get custom attributes for this product
        $customAttributes = $this->getProductCustomAttributes($product->id);

        // Map legacy fields to new estate structure
        $estateData = [
            'name' => $name,
            'slug' => $slug,
            'description' => $product->description,
            'price' => $product->price,
            'location_id' => $location->id,
            'category_id' => $category->id,
            'accepted' => $product->active == 1,
            'sold' => $product->closecase !== null,
            'published' => $product->active == 1,
            'address' => $product->address,
            'zip' => $product->postalcode,
            'custom_attributes' => $customAttributes,
            'created_at' => $product->created,
            'updated_at' => $product->created,
        ];

        Estate::create($estateData);
    }

    private function getProductCustomAttributes($productId): array
    {
        try {
            $attributes = DB::connection('temp')->table('product_values')
                ->where('product_id', $productId)
                ->get();

            $customAttributes = [];
            foreach ($attributes as $attr) {
                $customAttributes[$attr->name] = $attr->value;
            }

            return $customAttributes;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function mapCategoryId($legacyCategoryId): int
    {
        // Map legacy category IDs to new category IDs
        $categoryMapping = [
            1 => 1, // ház -> house
            2 => 2, // lakás -> flat
            3 => 3, // telek -> site
            4 => 4, // garázs -> garage
            5 => 5, // nyaraló -> summer house
            6 => 6, // iroda -> office
            7 => 7, // üzlethelység -> premises
            8 => 8, // ipari terület -> industrial area
            9 => 9, // fejlesztési terület -> development area
            10 => 10, // vendéglő -> inn
            11 => 11, // mezőgazdasági -> agricultural area
        ];

        return $categoryMapping[$legacyCategoryId] ?? 1; // Default to first category if not found
    }

    private function importSampleEstates(): void
    {
        $this->command->info('Importing sample estates from SQL dump data...');

        // Sample estates extracted from the SQL dump
        $sampleEstates = [
            [
                'id' => 373,
                'name' => 'Belvárosi lakás Pécsett',
                'description' => 'Pécsett a belvárosban a Garay utcában újszerű lakás kiadó! Amerika konyhás nappalis, 1 hálószobás, panoráma terasszos, klímás belvárosi lakás!',
                'category_id' => 2, // lakás
                'price' => 100000,
                'currency' => 'huf',
                'saletype' => 'rent',
                'area' => 55,
                'address' => 'Garay J.',
                'created' => '2012-02-23 06:22:00',
            ],
            [
                'id' => 520,
                'name' => 'Belvárosi garzon lakás',
                'description' => 'Belváros szivében Kiadó tégla-gázfűtéses, hálószoba galériás, teljesen berendezett, felszerelt garzon lakás alacsony rezsivel!',
                'category_id' => 2, // lakás
                'price' => 54000,
                'currency' => 'huf',
                'saletype' => 'rent',
                'area' => 36,
                'address' => 'Garay J. u. 12',
                'created' => '2011-05-02 02:10:38',
            ],
            [
                'id' => 552,
                'name' => 'Építési telek Görcsönyben',
                'description' => 'Görcsönyben új családi házas övezetben parkosított építési telek eladó! 30%-os beépíthetőség, 4,5M magasságig, összközműves',
                'category_id' => 3, // telek
                'price' => 3900000,
                'currency' => 'huf',
                'saletype' => 'sale',
                'area' => 0,
                'address' => null,
                'created' => '2011-11-16 03:51:50',
            ],
        ];

        foreach ($sampleEstates as $estateData) {
            $category = Category::skip($estateData['category_id'] - 1)->first();
            $location = Location::first();

            if (!$category || !$location) {
                $this->command->error('Missing required categories or locations. Please ensure they are seeded first.');
                continue;
            }

            $slug = Str::slug($estateData['name']) . '-' . $estateData['id'];

            Estate::create([
                'name' => $estateData['name'],
                'slug' => $slug,
                'description' => $estateData['description'],
                'price' => $estateData['price'],
                'location_id' => $location->id,
                'category_id' => $category->id,
                'accepted' => true,
                'sold' => false,
                'published' => true,
                'address' => $estateData['address'],
                'zip' => null,
                'custom_attributes' => [
                    'area' => $estateData['area'],
                    'currency' => $estateData['currency'],
                    'sale_type' => $estateData['saletype'],
                ],
                'created_at' => $estateData['created'],
                'updated_at' => $estateData['created'],
            ]);
        }

        $this->command->info('Sample estates imported successfully!');
    }
}