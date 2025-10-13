<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attributes = [
            // Általános jellemzők
            [
                'name' => 'Alapterület',
                'slug' => 'alapterulet',
                'type' => 'number',
                'description' => 'Az ingatlan alapterülete négyzetméterben (m²)',
                'options' => null
            ],
            [
                'name' => 'Szobák száma',
                'slug' => 'szobak-szama',
                'type' => 'select',
                'description' => 'Szobák száma',
                'options' => json_encode(['1', '1.5', '2', '2.5', '3', '3.5', '4', '4.5', '5', '5+'])
            ],
            [
                'name' => 'Hálószobák száma',
                'slug' => 'haloszobak-szama',
                'type' => 'select',
                'description' => 'Hálószobák száma',
                'options' => json_encode(['1', '2', '3', '4', '5', '6+'])
            ],
            [
                'name' => 'Fürdőszobák száma',
                'slug' => 'furdoszobak-szama',
                'type' => 'select',
                'description' => 'Fürdőszobák száma',
                'options' => json_encode(['1', '2', '3', '4+'])
            ],
            [
                'name' => 'Emelet',
                'slug' => 'emelet',
                'type' => 'select',
                'description' => 'Az ingatlan emelete',
                'options' => json_encode(['Földszint', 'Félemelet', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10+'])
            ],
            [
                'name' => 'Épület szintjei',
                'slug' => 'epulet-szintjei',
                'type' => 'select',
                'description' => 'Az épület összes szintje',
                'options' => json_encode(['1', '2', '3', '4', '5', '6', '7', '8', '9', '10+'])
            ],
            [
                'name' => 'Lift',
                'slug' => 'lift',
                'type' => 'boolean',
                'description' => 'Van-e lift az épületben',
                'options' => json_encode(['Igen', 'Nem'])
            ],
            [
                'name' => 'Parkolás',
                'slug' => 'parkolas',
                'type' => 'select',
                'description' => 'Parkolási lehetőség',
                'options' => json_encode(['Nincs', 'Utcai', 'Saját udvar', 'Garázs', 'Fedett parkoló', 'Mélygarázs'])
            ],
            [
                'name' => 'Parkolóhelyek száma',
                'slug' => 'parkolohelyek-szama',
                'type' => 'select',
                'description' => 'Parkolóhelyek száma',
                'options' => json_encode(['0', '1', '2', '3', '4+'])
            ],
            [
                'name' => 'Állapot',
                'slug' => 'allapot',
                'type' => 'select',
                'description' => 'Az ingatlan állapota',
                'options' => json_encode(['Újépítésű', 'Felújított', 'Jó állapot', 'Közepes állapot', 'Felújítandó', 'Bontandó'])
            ],
            [
                'name' => 'Építés éve',
                'slug' => 'epites-eve',
                'type' => 'number',
                'description' => 'Az épület építésének éve',
                'options' => null
            ],
            [
                'name' => 'Fűtés',
                'slug' => 'futes',
                'type' => 'select',
                'description' => 'Fűtés típusa',
                'options' => json_encode(['Gáz (cirkó)', 'Gáz (konvektor)', 'Távfűtés', 'Elektromos', 'Vegyes tüzelésű', 'Kandalló', 'Hőszivattyú', 'Geotermikus'])
            ],
            [
                'name' => 'Energetikai besorolás',
                'slug' => 'energetikai-besorolas',
                'type' => 'select',
                'description' => 'Energetikai tanúsítvány besorolása',
                'options' => json_encode(['A++', 'A+', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'])
            ],
            [
                'name' => 'Kilátás',
                'slug' => 'kilatas',
                'type' => 'select',
                'description' => 'Kilátás az ingatlanból',
                'options' => json_encode(['Utcai', 'Udvari', 'Kertre néző', 'Panorámás', 'Vízre néző', 'Hegyre néző', 'Parkra néző'])
            ],
            [
                'name' => 'Erkély/Terasz',
                'slug' => 'erkely-terasz',
                'type' => 'select',
                'description' => 'Erkély vagy terasz',
                'options' => json_encode(['Nincs', 'Erkély', 'Loggia', 'Terasz', 'Francia erkély', 'Több erkély'])
            ],
            [
                'name' => 'Kert',
                'slug' => 'kert',
                'type' => 'boolean',
                'description' => 'Van-e kert',
                'options' => json_encode(['Igen', 'Nem'])
            ],
            [
                'name' => 'Kert mérete',
                'slug' => 'kert-merete',
                'type' => 'number',
                'description' => 'A kert mérete négyzetméterben (m²)',
                'options' => null
            ],
            [
                'name' => 'Bútorozott',
                'slug' => 'butorozott',
                'type' => 'select',
                'description' => 'Bútorozott-e az ingatlan',
                'options' => json_encode(['Igen', 'Nem', 'Részben', 'Megegyezés szerint'])
            ],
            [
                'name' => 'Háziállat',
                'slug' => 'haziallat',
                'type' => 'select',
                'description' => 'Háziállat tartható-e',
                'options' => json_encode(['Igen', 'Nem', 'Megegyezés szerint'])
            ],
            [
                'name' => 'Dohányzás',
                'slug' => 'dohanyzas',
                'type' => 'select',
                'description' => 'Dohányzás engedélyezett-e',
                'options' => json_encode(['Igen', 'Nem', 'Csak a teraszón/erkélyen'])
            ],
            [
                'name' => 'Internet',
                'slug' => 'internet',
                'type' => 'select',
                'description' => 'Internet elérhetőség',
                'options' => json_encode(['WiFi', 'Kábelnet', 'Optikai', 'ADSL', 'Nincs'])
            ],
            [
                'name' => 'Légkondicionáló',
                'slug' => 'legkondicionalo',
                'type' => 'boolean',
                'description' => 'Van-e légkondicionáló',
                'options' => json_encode(['Igen', 'Nem'])
            ],
            [
                'name' => 'Gépkocsi behajtás',
                'slug' => 'gepkocsi-behajtas',
                'type' => 'boolean',
                'description' => 'Gépkocsival megközelíthető-e',
                'options' => json_encode(['Igen', 'Nem'])
            ],
            [
                'name' => 'Akadálymentesített',
                'slug' => 'akadalymentesitett',
                'type' => 'boolean',
                'description' => 'Akadálymentesített-e az ingatlan',
                'options' => json_encode(['Igen', 'Nem'])
            ],
            [
                'name' => 'Pince',
                'slug' => 'pince',
                'type' => 'boolean',
                'description' => 'Van-e pince',
                'options' => json_encode(['Igen', 'Nem'])
            ],
            [
                'name' => 'Padlástér',
                'slug' => 'padlaster',
                'type' => 'boolean',
                'description' => 'Van-e padlástér',
                'options' => json_encode(['Igen', 'Nem'])
            ],
            [
                'name' => 'Riasztó',
                'slug' => 'riaszto',
                'type' => 'boolean',
                'description' => 'Van-e riasztórendszer',
                'options' => json_encode(['Igen', 'Nem'])
            ],
            [
                'name' => 'Közös költség',
                'slug' => 'kozos-koltseg',
                'type' => 'number',
                'description' => 'Havi közös költség (Ft/hó)',
                'options' => null
            ],
            [
                'name' => 'Vízmelegítés',
                'slug' => 'vizmelegites',
                'type' => 'select',
                'description' => 'Vízmelegítés típusa',
                'options' => json_encode(['Gázkazán', 'Elektromos bojler', 'Központi', 'Napkollektor', 'Átfolyós vízmelegítő'])
            ],
            [
                'name' => 'Tájolás',
                'slug' => 'tajolas',
                'type' => 'select',
                'description' => 'Az ingatlan tájolása',
                'options' => json_encode(['Északi', 'Északkeleti', 'Keleti', 'Délkeleti', 'Déli', 'Délnyugati', 'Nyugati', 'Északnyugati'])
            ],
            [
                'name' => 'Szomszédság',
                'slug' => 'szomszedság',
                'type' => 'select',
                'description' => 'Szomszédos területek',
                'options' => json_encode(['Lakóövezet', 'Ipari terület', 'Kereskedelmi terület', 'Park', 'Erdő', 'Mezőgazdasági terület'])
            ]
        ];

        foreach ($attributes as $attribute) {
            DB::table('attributes')->insert([
                'name' => $attribute['name'],
                'slug' => $attribute['slug'],
                'type' => $attribute['type'],
                'description' => $attribute['description'],
                'options' => $attribute['options'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Created ' . count($attributes) . ' Hungarian attributes successfully!');
    }
}