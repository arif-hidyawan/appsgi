<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            // 1. Satuan Dasar (Eceran)
            ['name' => 'Pieces', 'code' => 'PCS'],
            ['name' => 'Unit', 'code' => 'UNIT'],
            ['name' => 'Buah', 'code' => 'BH'],

            // 2. Satuan Kemasan (Packaging - Paling sering di B2B)
            ['name' => 'Box', 'code' => 'BOX'],
            ['name' => 'Carton', 'code' => 'CTN'], // Karton/Dus
            ['name' => 'Pack', 'code' => 'PAK'],
            ['name' => 'Bundle', 'code' => 'BDL'], // Ikat/Bendel
            ['name' => 'Sack', 'code' => 'SAK'], // Karung (Semen/Beras)
            ['name' => 'Pallet', 'code' => 'PLT'], // Palet (Logistik)
            ['name' => 'Bag', 'code' => 'BAG'], // Tas/Kantong

            // 3. Satuan Kuantitas (Jumlah)
            ['name' => 'Set', 'code' => 'SET'],
            ['name' => 'Pair', 'code' => 'PR'], // Pasang (Sepatu/Sarung Tangan)
            ['name' => 'Dozen', 'code' => 'DZN'], // Lusin (12)
            ['name' => 'Gross', 'code' => 'GRS'], // Gross (144)

            // 4. Satuan Berat (Weight)
            ['name' => 'Kilogram', 'code' => 'KG'],
            ['name' => 'Gram', 'code' => 'GR'],
            ['name' => 'Ton', 'code' => 'TON'],

            // 5. Satuan Volume (Cairan/Kimia)
            ['name' => 'Liter', 'code' => 'LTR'],
            ['name' => 'Milliliter', 'code' => 'ML'],
            ['name' => 'Drum', 'code' => 'DRM'], // Drum Minyak/Kimia
            ['name' => 'Gallon', 'code' => 'GAL'],
            ['name' => 'Pail', 'code' => 'PAIL'], // Ember Cat/Kimia

            // 6. Satuan Dimensi (Panjang/Luas - Kabel/Kain/Bahan Bangunan)
            ['name' => 'Meter', 'code' => 'MTR'],
            ['name' => 'Roll', 'code' => 'ROL'], // Gulungan
            ['name' => 'Square Meter', 'code' => 'M2'], // Meter Persegi (Keramik/Vinyl)
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(
                ['code' => $unit['code']], // Cek agar tidak duplikat berdasarkan Kode
                [
                    'name' => $unit['name'], 
                    'is_active' => true
                ]
            );
        }
    }
}