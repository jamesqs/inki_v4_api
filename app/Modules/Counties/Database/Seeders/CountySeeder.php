<?php

namespace App\Modules\Counties\Database\Seeders;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;

class CountySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $counties = [
            ['name' => 'Bács-Kiskun', 'country_code' => 'HU'],
            ['name' => 'Baranya', 'country_code' => 'HU'],
            ['name' => 'Békés', 'country_code' => 'HU'],
            ['name' => 'Borsod-Abaúj-Zemplén', 'country_code' => 'HU'],
            ['name' => 'Csongrád-Csanád', 'country_code' => 'HU'],
            ['name' => 'Fejér', 'country_code' => 'HU'],
            ['name' => 'Győr-Moson-Sopron', 'country_code' => 'HU'],
            ['name' => 'Hajdú-Bihar', 'country_code' => 'HU'],
            ['name' => 'Heves', 'country_code' => 'HU'],
            ['name' => 'Jász-Nagykun-Szolnok', 'country_code' => 'HU'],
            ['name' => 'Komárom-Esztergom', 'country_code' => 'HU'],
            ['name' => 'Nógrád', 'country_code' => 'HU'],
            ['name' => 'Pest', 'country_code' => 'HU'],
            ['name' => 'Somogy', 'country_code' => 'HU'],
            ['name' => 'Szabolcs-Szatmár-Bereg', 'country_code' => 'HU'],
            ['name' => 'Tolna', 'country_code' => 'HU'],
            ['name' => 'Vas', 'country_code' => 'HU'],
            ['name' => 'Veszprém', 'country_code' => 'HU'],
            ['name' => 'Zala', 'country_code' => 'HU']
        ];

        DB::table('counties')->insert($counties);
    }
}
