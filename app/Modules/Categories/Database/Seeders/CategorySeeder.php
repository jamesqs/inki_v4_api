<?php

namespace App\Modules\Categories\Database\Seeders;

use App\Modules\Categories\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Lakás', 'description' => 'lakások' ],
            ['name' => 'Ház', 'description' => 'házak' ],
            ['name' => 'Telek', 'description' => 'telkek' ],
            ['name' => 'Iroda', 'description' => 'irodák' ],
            ['name' => 'Üzlethelyiség', 'description' => 'üzlethelyiségek' ],
            ['name' => 'Raktár', 'description' => 'raktárak' ],
            ['name' => 'Mezőgazdasági ingatlan', 'description' => 'mezőgazdasági ingatlanok' ],
            ['name' => 'Egyéb', 'description' => 'egyéb ingatlanok' ],
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => $category['description']
            ]);
        }
    }
}
