<?php

namespace App\Modules\Locations\Database\Seeders;

use App\Modules\Locations\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            ['name' => 'Budapest', 'importance' => 10, 'county_id' => 13, 'has_districts' => true], // Pest county (Budapest)
            ['name' => 'Debrecen', 'importance' => 9, 'county_id' => 8], // Hajdú-Bihar
            ['name' => 'Szeged', 'importance' => 9, 'county_id' => 5], // Csongrád-Csanád
            ['name' => 'Miskolc', 'importance' => 8, 'county_id' => 4], // Borsod-Abaúj-Zemplén
            ['name' => 'Pécs', 'importance' => 8, 'county_id' => 2], // Baranya
            ['name' => 'Győr', 'importance' => 8, 'county_id' => 7], // Győr-Moson-Sopron
            ['name' => 'Nyíregyháza', 'importance' => 7, 'county_id' => 15], // Szabolcs-Szatmár-Bereg
            ['name' => 'Kecskemét', 'importance' => 7, 'county_id' => 1], // Bács-Kiskun
            ['name' => 'Székesfehérvár', 'importance' => 7, 'county_id' => 6], // Fejér
            ['name' => 'Szombathely', 'importance' => 6, 'county_id' => 17], // Vas
            ['name' => 'Szolnok', 'importance' => 6, 'county_id' => 10], // Jász-Nagykun-Szolnok
            ['name' => 'Tatabánya', 'importance' => 6, 'county_id' => 11], // Komárom-Esztergom
            ['name' => 'Sopron', 'importance' => 6, 'county_id' => 7], // Győr-Moson-Sopron
            ['name' => 'Kaposvár', 'importance' => 6, 'county_id' => 14], // Somogy
            ['name' => 'Veszprém', 'importance' => 6, 'county_id' => 18], // Veszprém
            ['name' => 'Békéscsaba', 'importance' => 6, 'county_id' => 3], // Békés
            ['name' => 'Eger', 'importance' => 6, 'county_id' => 9], // Heves
            ['name' => 'Zalaegerszeg', 'importance' => 5, 'county_id' => 19], // Zala
            ['name' => 'Baja', 'importance' => 5, 'county_id' => 1], // Bács-Kiskun
            ['name' => 'Hódmezővásárhely', 'importance' => 5, 'county_id' => 5], // Csongrád-Csanád
            ['name' => 'Siófok', 'importance' => 5, 'county_id' => 14], // Somogy
            ['name' => 'Esztergom', 'importance' => 5, 'county_id' => 11], // Komárom-Esztergom
            ['name' => 'Gyöngyös', 'importance' => 4, 'county_id' => 9], // Heves
            ['name' => 'Sárospatak', 'importance' => 4, 'county_id' => 4], // Borsod-Abaúj-Zemplén
            ['name' => 'Szentendre', 'importance' => 4, 'county_id' => 13], // Pest
            ['name' => 'Kőszeg', 'importance' => 4, 'county_id' => 17], // Vas
            ['name' => 'Mohács', 'importance' => 4, 'county_id' => 2], // Baranya
            ['name' => 'Balatonfüred', 'importance' => 5, 'county_id' => 18], // Veszprém
            ['name' => 'Hajdúszoboszló', 'importance' => 5, 'county_id' => 8], // Hajdú-Bihar
            ['name' => 'Visegrád', 'importance' => 4, 'county_id' => 13], // Pest
            ['name' => 'Tokaj', 'importance' => 4, 'county_id' => 4], // Borsod-Abaúj-Zemplén
            ['name' => 'Hévíz', 'importance' => 5, 'county_id' => 19], // Zala
            ['name' => 'Gödöllő', 'importance' => 4, 'county_id' => 13], // Pest
            ['name' => 'Cegléd', 'importance' => 4, 'county_id' => 13], // Pest
            ['name' => 'Hatvan', 'importance' => 3, 'county_id' => 9], // Heves
            ['name' => 'Balaton', 'importance' => 7, 'county_id' => 18], // Veszprém (mostly)
            ['name' => 'Hortobágy', 'importance' => 5, 'county_id' => 8], // Hajdú-Bihar
            ['name' => 'Aggtelek', 'importance' => 4, 'county_id' => 4], // Borsod-Abaúj-Zemplén
            ['name' => 'Villány', 'importance' => 3, 'county_id' => 2], // Baranya
            ['name' => 'Tihany', 'importance' => 4, 'county_id' => 18], // Veszprém
            ['name' => 'Hollókő', 'importance' => 3, 'county_id' => 12], // Nógrád
            ['name' => 'Dunakanyar', 'importance' => 5, 'county_id' => 13], // Pest
            ['name' => 'Pápa', 'importance' => 3, 'county_id' => 18], // Veszprém
            ['name' => 'Sárvár', 'importance' => 4, 'county_id' => 17], // Vas
            ['name' => 'Keszthely', 'importance' => 4, 'county_id' => 19], // Zala
            ['name' => 'Szigetvár', 'importance' => 3, 'county_id' => 2], // Baranya
            ['name' => 'Ópusztaszer', 'importance' => 3, 'county_id' => 5], // Csongrád-Csanád
            ['name' => 'Makó', 'importance' => 3, 'county_id' => 5], // Csongrád-Csanád
            ['name' => 'Gyula', 'importance' => 4, 'county_id' => 3], // Békés
            ['name' => 'Salgótarján', 'importance' => 5, 'county_id' => 12], // Nógrád
        ];

        foreach ($locations as $location) {
            Location::create([
                'name' => $location['name'],
                'slug' => Str::slug($location['name']),
                'importance' => $location['importance'],
                'county_id' => $location['county_id'],
            ]);
        }
    }
}
