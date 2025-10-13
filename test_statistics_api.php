<?php

require_once 'vendor/autoload.php';

use App\Modules\Estates\Models\Estate;
use App\Modules\Categories\Models\Category;
use App\Modules\Locations\Models\Location;

// Create some test estate data
$locations = Location::take(5)->get();
$categories = Category::take(3)->get();

if ($locations->count() > 0 && $categories->count() > 0) {
    $location = $locations->first();
    $category = $categories->first();

    // Create test estates with different prices and attributes
    $testEstates = [
        [
            'name' => 'Cozy Apartment Downtown',
            'slug' => 'cozy-apartment-downtown-1',
            'description' => 'Beautiful 2-bedroom apartment in the city center',
            'price' => 250000,
            'location_id' => $location->id,
            'category_id' => $category->id,
            'published' => true,
            'sold' => false,
            'custom_attributes' => [
                'bedrooms' => '2',
                'bathrooms' => '1',
                'square_meters' => '65',
                'floor' => '3',
                'parking' => 'yes'
            ]
        ],
        [
            'name' => 'Modern Family House',
            'slug' => 'modern-family-house-1',
            'description' => 'Spacious family house with garden',
            'price' => 420000,
            'location_id' => $location->id,
            'category_id' => $category->id,
            'published' => true,
            'sold' => false,
            'custom_attributes' => [
                'bedrooms' => '4',
                'bathrooms' => '2',
                'square_meters' => '120',
                'garden' => 'yes',
                'parking' => 'yes'
            ]
        ],
        [
            'name' => 'Studio Apartment',
            'slug' => 'studio-apartment-1',
            'description' => 'Compact studio in great location',
            'price' => 180000,
            'location_id' => $location->id,
            'category_id' => $category->id,
            'published' => true,
            'sold' => true,
            'custom_attributes' => [
                'bedrooms' => '1',
                'bathrooms' => '1',
                'square_meters' => '35',
                'floor' => '2',
                'parking' => 'no'
            ]
        ],
        [
            'name' => 'Luxury Penthouse',
            'slug' => 'luxury-penthouse-1',
            'description' => 'Premium penthouse with amazing views',
            'price' => 850000,
            'location_id' => $location->id,
            'category_id' => $category->id,
            'published' => true,
            'sold' => false,
            'custom_attributes' => [
                'bedrooms' => '3',
                'bathrooms' => '3',
                'square_meters' => '150',
                'floor' => '10',
                'terrace' => 'yes',
                'parking' => 'yes'
            ]
        ],
        [
            'name' => 'Budget Apartment',
            'slug' => 'budget-apartment-1',
            'description' => 'Affordable option for first-time buyers',
            'price' => 150000,
            'location_id' => $location->id,
            'category_id' => $category->id,
            'published' => true,
            'sold' => false,
            'custom_attributes' => [
                'bedrooms' => '1',
                'bathrooms' => '1',
                'square_meters' => '45',
                'floor' => '1',
                'parking' => 'no'
            ]
        ]
    ];

    foreach ($testEstates as $estateData) {
        Estate::create($estateData);
    }

    echo "Created " . count($testEstates) . " test estates\n";
    echo "Location ID: " . $location->id . " (" . $location->name . ")\n";
    echo "Category ID: " . $category->id . " (" . $category->name . ")\n";
} else {
    echo "No locations or categories found. Please seed the database first.\n";
}