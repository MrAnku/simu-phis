<?php

namespace Database\Seeders;

use App\Models\RegionDb;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RegionDbSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RegionDb::insert([
            [
                'region_name' => 'IND',
                'db_host' => '127.0.0.1',
                'db_port' => 3306,
                'db_database' => 'simu_lara',
                'db_username' => 'root',
            ],
            [
                'region_name' => 'UK',
                'db_host' => '127.0.0.1',
                'db_port' => 3306,
                'db_database' => 'test_simu_lara',
                'db_username' => 'root',
            ],
        ]);
    }
}
