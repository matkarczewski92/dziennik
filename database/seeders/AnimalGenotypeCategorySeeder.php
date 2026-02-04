<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnimalGenotypeCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('animal_genotype_category')->upsert([
            ['id' => 2, 'name' => 'Amel', 'gene_code' => 'am', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 3, 'name' => 'Anery', 'gene_code' => 'an', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 4, 'name' => 'Scaleless', 'gene_code' => 'sc', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 5, 'name' => 'Stripe', 'gene_code' => 'st', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 7, 'name' => 'Motley', 'gene_code' => 'mo', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 8, 'name' => 'Motley/Stripe', 'gene_code' => 'ms', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 9, 'name' => 'Ultra', 'gene_code' => 'ui', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 10, 'name' => 'Amel/Ultra', 'gene_code' => 'au', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 11, 'name' => 'Caramel', 'gene_code' => 'ca', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 12, 'name' => 'Bloodred', 'gene_code' => 'bl', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 13, 'name' => 'Sunkissed', 'gene_code' => 'su', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 14, 'name' => 'Hypo', 'gene_code' => 'hy', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 15, 'name' => 'Lava', 'gene_code' => 'lv', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 16, 'name' => 'Cinder', 'gene_code' => 'ci', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 17, 'name' => 'Diffused', 'gene_code' => 'di', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 18, 'name' => 'Charcoal', 'gene_code' => 'ch', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 19, 'name' => 'Okeetee', 'gene_code' => 'OK', 'gene_type' => 'd', 'created_at' => null, 'updated_at' => null],
            ['id' => 21, 'name' => 'Extreme Okeetee', 'gene_code' => 'eo', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 22, 'name' => 'Tessera', 'gene_code' => 'TT', 'gene_type' => 'd', 'created_at' => null, 'updated_at' => null],
            ['id' => 23, 'name' => 'Palmetto', 'gene_code' => 'pa', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 35, 'name' => 'Lavender', 'gene_code' => 'la', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 36, 'name' => 'Redfactor', 'gene_code' => 're', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 37, 'name' => 'Masque', 'gene_code' => 'ma', 'gene_type' => 'r', 'created_at' => null, 'updated_at' => null],
            ['id' => 39, 'name' => 'Dilute', 'gene_code' => 'dl', 'gene_type' => 'r', 'created_at' => '2024-11-24 12:21:31', 'updated_at' => '2024-11-24 12:21:31'],
            ['id' => 40, 'name' => 'Kastanie', 'gene_code' => 'ks', 'gene_type' => 'r', 'created_at' => '2024-11-24 12:29:00', 'updated_at' => '2024-11-24 12:29:00'],
        ], ['id'], ['name', 'gene_code', 'gene_type', 'created_at', 'updated_at']);
    }
}

