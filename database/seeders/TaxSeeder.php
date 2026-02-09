<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        $taxes = [
            ['name' => 'PPN 11%', 'rate' => 11.00, 'priority' => 1],
            ['name' => 'PPN 12% (Next)', 'rate' => 12.00, 'priority' => 2, 'is_active' => false], // Persiapan
            ['name' => 'PPh 23 (Jasa)', 'rate' => 2.00, 'priority' => 3],
            ['name' => 'PPh 21 (Final)', 'rate' => 0.50, 'priority' => 4],
            ['name' => 'Non-PPN', 'rate' => 0.00, 'priority' => 99],
        ];

        foreach ($taxes as $tax) {
            Tax::create($tax);
        }
    }
}