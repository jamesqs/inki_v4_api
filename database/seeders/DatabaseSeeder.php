<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

use App\Modules\Locations\Database\Seeders\LocationSeeder;
use App\Modules\Categories\Database\Seeders\CategorySeeder;
use App\Modules\Counties\Database\Seeders\CountySeeder;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Add the new user
        User::factory()->create([
            'first_name' => 'James',
            'last_name' => 'Taylor',
            'email' => 'james.szj@gmail.com',
            'role' => 'admin',
            'password' => Hash::make('123123123'),
        ]);

        $this->call([
            CountySeeder::class,
            LocationSeeder::class,
            CategorySeeder::class,
        ]);
    }
}
