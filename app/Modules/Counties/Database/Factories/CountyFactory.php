<?php

namespace App\Modules\Counties\Database\Factories;

use App\Modules\Counties\Models\County;
use Illuminate\Database\Eloquent\Factories\Factory;

class CountyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = County::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            // Define your factory attributes here
        ];
    }
}