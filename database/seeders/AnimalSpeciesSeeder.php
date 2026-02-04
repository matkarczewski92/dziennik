<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnimalSpeciesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('animal_species')->upsert([
            [
                'id' => 1,
                'name' => 'Waz zbozowy (Pantherophis Guttatus)',
                'created_at' => '2023-11-04 16:00:44',
                'updated_at' => '2023-11-04 16:00:44',
            ],
            [
                'id' => 2,
                'name' => 'Pyton krolewski (Python regius)',
                'created_at' => '2025-09-14 14:37:40',
                'updated_at' => '2025-09-14 14:37:48',
            ],
            [
                'id' => 5,
                'name' => 'Poloz amurski (Elaphe schrenckii)',
                'created_at' => '2025-10-12 18:09:33',
                'updated_at' => '2025-10-12 18:09:33',
            ],
            [
                'id' => 6,
                'name' => 'Agama brodata (Pogona vitticeps)',
                'created_at' => '2025-11-04 05:20:06',
                'updated_at' => '2025-11-04 05:20:06',
            ],
        ], ['id'], ['name', 'created_at', 'updated_at']);
    }
}

