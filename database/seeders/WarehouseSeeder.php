<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::create([
            'code' => 'MAIN',
            'name' => 'Gudang Utama (Ready)',
            'address' => 'Gudang Pusat',
            'is_default' => true,
        ]);

        Warehouse::create([
            'code' => 'REJECT',
            'name' => 'Gudang Rusak / Bad Stock',
            'address' => 'Area Karantina',
            'is_default' => false,
        ]);
    }
}