<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all categories and attributes
        $categories = DB::table('categories')->pluck('id', 'slug');
        $attributes = DB::table('attributes')->pluck('id', 'slug');

        // Define which attributes belong to which categories
        $categoryAttributeMap = [
            // Lakás (Apartment)
            'lakas' => [
                'alapterulet' => ['required' => true, 'order' => 1],
                'szobak-szama' => ['required' => true, 'order' => 2],
                'haloszobak-szama' => ['required' => true, 'order' => 3],
                'furdoszobak-szama' => ['required' => true, 'order' => 4],
                'emelet' => ['required' => true, 'order' => 5],
                'epulet-szintjei' => ['required' => false, 'order' => 6],
                'lift' => ['required' => false, 'order' => 7],
                'parkolas' => ['required' => true, 'order' => 8],
                'parkolohelyek-szama' => ['required' => false, 'order' => 9],
                'allapot' => ['required' => true, 'order' => 10],
                'epites-eve' => ['required' => false, 'order' => 11],
                'futes' => ['required' => true, 'order' => 12],
                'energetikai-besorolas' => ['required' => false, 'order' => 13],
                'kilatas' => ['required' => false, 'order' => 14],
                'erkely-terasz' => ['required' => false, 'order' => 15],
                'butorozott' => ['required' => false, 'order' => 16],
                'haziallat' => ['required' => false, 'order' => 17],
                'dohanyzas' => ['required' => false, 'order' => 18],
                'internet' => ['required' => false, 'order' => 19],
                'legkondicionalo' => ['required' => false, 'order' => 20],
                'akadalymentesitett' => ['required' => false, 'order' => 21],
                'pince' => ['required' => false, 'order' => 22],
                'riaszto' => ['required' => false, 'order' => 23],
                'kozos-koltseg' => ['required' => false, 'order' => 24],
                'vizmelegites' => ['required' => false, 'order' => 25],
                'tajolas' => ['required' => false, 'order' => 26],
            ],

            // Ház (House)
            'haz' => [
                'alapterulet' => ['required' => true, 'order' => 1],
                'szobak-szama' => ['required' => true, 'order' => 2],
                'haloszobak-szama' => ['required' => true, 'order' => 3],
                'furdoszobak-szama' => ['required' => true, 'order' => 4],
                'epulet-szintjei' => ['required' => true, 'order' => 5],
                'parkolas' => ['required' => true, 'order' => 6],
                'parkolohelyek-szama' => ['required' => false, 'order' => 7],
                'allapot' => ['required' => true, 'order' => 8],
                'epites-eve' => ['required' => false, 'order' => 9],
                'futes' => ['required' => true, 'order' => 10],
                'energetikai-besorolas' => ['required' => false, 'order' => 11],
                'kilatas' => ['required' => false, 'order' => 12],
                'erkely-terasz' => ['required' => false, 'order' => 13],
                'kert' => ['required' => false, 'order' => 14],
                'kert-merete' => ['required' => false, 'order' => 15],
                'butorozott' => ['required' => false, 'order' => 16],
                'haziallat' => ['required' => false, 'order' => 17],
                'dohanyzas' => ['required' => false, 'order' => 18],
                'internet' => ['required' => false, 'order' => 19],
                'legkondicionalo' => ['required' => false, 'order' => 20],
                'gepkocsi-behajtas' => ['required' => false, 'order' => 21],
                'akadalymentesitett' => ['required' => false, 'order' => 22],
                'pince' => ['required' => false, 'order' => 23],
                'padlaster' => ['required' => false, 'order' => 24],
                'riaszto' => ['required' => false, 'order' => 25],
                'vizmelegites' => ['required' => false, 'order' => 26],
                'tajolas' => ['required' => false, 'order' => 27],
                'szomszedság' => ['required' => false, 'order' => 28],
            ],

            // Telek (Plot)
            'telek' => [
                'alapterulet' => ['required' => true, 'order' => 1],
                'gepkocsi-behajtas' => ['required' => false, 'order' => 2],
                'szomszedság' => ['required' => false, 'order' => 3],
                'tajolas' => ['required' => false, 'order' => 4],
                'kilatas' => ['required' => false, 'order' => 5],
            ],

            // Iroda (Office)
            'iroda' => [
                'alapterulet' => ['required' => true, 'order' => 1],
                'szobak-szama' => ['required' => false, 'order' => 2],
                'emelet' => ['required' => true, 'order' => 3],
                'epulet-szintjei' => ['required' => false, 'order' => 4],
                'lift' => ['required' => false, 'order' => 5],
                'parkolas' => ['required' => true, 'order' => 6],
                'parkolohelyek-szama' => ['required' => false, 'order' => 7],
                'allapot' => ['required' => true, 'order' => 8],
                'epites-eve' => ['required' => false, 'order' => 9],
                'futes' => ['required' => true, 'order' => 10],
                'energetikai-besorolas' => ['required' => false, 'order' => 11],
                'kilatas' => ['required' => false, 'order' => 12],
                'internet' => ['required' => true, 'order' => 13],
                'legkondicionalo' => ['required' => false, 'order' => 14],
                'akadalymentesitett' => ['required' => false, 'order' => 15],
                'riaszto' => ['required' => false, 'order' => 16],
                'kozos-koltseg' => ['required' => false, 'order' => 17],
                'tajolas' => ['required' => false, 'order' => 18],
            ],

            // Üzlethelyiség (Commercial Space)
            'uzlethelyiseg' => [
                'alapterulet' => ['required' => true, 'order' => 1],
                'emelet' => ['required' => true, 'order' => 2],
                'lift' => ['required' => false, 'order' => 3],
                'parkolas' => ['required' => true, 'order' => 4],
                'parkolohelyek-szama' => ['required' => false, 'order' => 5],
                'allapot' => ['required' => true, 'order' => 6],
                'epites-eve' => ['required' => false, 'order' => 7],
                'futes' => ['required' => true, 'order' => 8],
                'energetikai-besorolas' => ['required' => false, 'order' => 9],
                'internet' => ['required' => false, 'order' => 10],
                'legkondicionalo' => ['required' => false, 'order' => 11],
                'akadalymentesitett' => ['required' => false, 'order' => 12],
                'riaszto' => ['required' => false, 'order' => 13],
                'kozos-koltseg' => ['required' => false, 'order' => 14],
                'gepkocsi-behajtas' => ['required' => false, 'order' => 15],
            ],

            // Raktár (Warehouse)
            'raktar' => [
                'alapterulet' => ['required' => true, 'order' => 1],
                'emelet' => ['required' => false, 'order' => 2],
                'epulet-szintjei' => ['required' => false, 'order' => 3],
                'parkolas' => ['required' => true, 'order' => 4],
                'allapot' => ['required' => true, 'order' => 5],
                'epites-eve' => ['required' => false, 'order' => 6],
                'futes' => ['required' => false, 'order' => 7],
                'riaszto' => ['required' => false, 'order' => 8],
                'gepkocsi-behajtas' => ['required' => true, 'order' => 9],
                'szomszedság' => ['required' => false, 'order' => 10],
            ],

            // Mezőgazdasági ingatlan (Agricultural Property)
            'mezogazdasagi-ingatlan' => [
                'alapterulet' => ['required' => true, 'order' => 1],
                'gepkocsi-behajtas' => ['required' => true, 'order' => 2],
                'szomszedság' => ['required' => false, 'order' => 3],
                'kilatas' => ['required' => false, 'order' => 4],
                'tajolas' => ['required' => false, 'order' => 5],
            ],

            // Egyéb (Other)
            'egyeb' => [
                'alapterulet' => ['required' => true, 'order' => 1],
                'allapot' => ['required' => false, 'order' => 2],
                'epites-eve' => ['required' => false, 'order' => 3],
                'parkolas' => ['required' => false, 'order' => 4],
                'gepkocsi-behajtas' => ['required' => false, 'order' => 5],
                'szomszedság' => ['required' => false, 'order' => 6],
            ],
        ];

        $insertData = [];

        foreach ($categoryAttributeMap as $categorySlug => $categoryAttributes) {
            if (!isset($categories[$categorySlug])) {
                continue;
            }

            $categoryId = $categories[$categorySlug];

            foreach ($categoryAttributes as $attributeSlug => $config) {
                if (!isset($attributes[$attributeSlug])) {
                    continue;
                }

                $attributeId = $attributes[$attributeSlug];

                $insertData[] = [
                    'category_id' => $categoryId,
                    'attribute_id' => $attributeId,
                    'required' => $config['required'],
                    'order' => $config['order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insert all relationships
        DB::table('attribute_category')->insert($insertData);

        $this->command->info('Created ' . count($insertData) . ' category-attribute relationships successfully!');
    }
}