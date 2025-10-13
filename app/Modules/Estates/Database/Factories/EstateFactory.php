<?php

namespace App\Modules\Estates\Database\Factories;

use App\Modules\Estates\Models\Estate;
use Illuminate\Database\Eloquent\Factories\Factory;

class EstateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Estate::class;

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