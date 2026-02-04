<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeedSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('feeds')->upsert([
            ['id' => 1, 'name' => 'Osesek 1-2g', 'feeding_interval' => 5, 'amount' => 385, 'last_price' => 0.80, 'created_at' => null, 'updated_at' => '2026-01-29 18:02:34'],
            ['id' => 2, 'name' => 'Osesek 3-4g', 'feeding_interval' => 6, 'amount' => 205, 'last_price' => 1.00, 'created_at' => null, 'updated_at' => '2026-01-29 18:01:23'],
            ['id' => 3, 'name' => 'Osesek 5-9g', 'feeding_interval' => 6, 'amount' => 127, 'last_price' => 1.20, 'created_at' => null, 'updated_at' => '2026-01-29 18:01:23'],
            ['id' => 4, 'name' => 'Mysz 10-16g', 'feeding_interval' => 8, 'amount' => 31, 'last_price' => 1.80, 'created_at' => null, 'updated_at' => '2026-01-29 18:01:23'],
            ['id' => 5, 'name' => 'Mysz 16-22g', 'feeding_interval' => 8, 'amount' => 39, 'last_price' => 2.20, 'created_at' => null, 'updated_at' => '2025-12-17 18:57:04'],
            ['id' => 6, 'name' => 'Mysz 23-29g', 'feeding_interval' => 9, 'amount' => 63, 'last_price' => 2.40, 'created_at' => null, 'updated_at' => '2026-01-29 18:01:23'],
            ['id' => 7, 'name' => 'Szczur mrozony 30/40g', 'feeding_interval' => 23, 'amount' => 0, 'last_price' => 1.30, 'created_at' => null, 'updated_at' => '2026-01-22 16:15:44'],
            ['id' => 9, 'name' => 'Odmowa przyjecia pokarmu', 'feeding_interval' => 0, 'amount' => 99823, 'last_price' => 0.00, 'created_at' => '2023-11-04 16:00:44', 'updated_at' => '2026-01-29 18:02:34'],
            ['id' => 10, 'name' => '-- Nie karmiony --', 'feeding_interval' => 99, 'amount' => 995, 'last_price' => 99.00, 'created_at' => '2024-11-24 12:35:39', 'updated_at' => '2026-01-18 08:31:29'],
            ['id' => 11, 'name' => 'Szczur 5-9g', 'feeding_interval' => 5, 'amount' => 49, 'last_price' => 1.30, 'created_at' => '2025-09-14 16:07:23', 'updated_at' => '2026-01-29 18:01:23'],
            ['id' => 12, 'name' => 'Owady', 'feeding_interval' => 9999, 'amount' => 9993, 'last_price' => 1.00, 'created_at' => '2025-11-04 05:20:27', 'updated_at' => '2026-01-06 18:11:38'],
            ['id' => 13, 'name' => 'Myszy 30g+', 'feeding_interval' => 10, 'amount' => 59, 'last_price' => 2.60, 'created_at' => '2025-12-07 15:19:18', 'updated_at' => '2026-01-29 18:01:23'],
        ], ['id'], ['name', 'feeding_interval', 'amount', 'last_price', 'created_at', 'updated_at']);
    }
}

