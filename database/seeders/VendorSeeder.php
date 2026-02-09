<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Vendor;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan file CSV Anda bernama 'vendors.csv' dan ada di folder 'database/data/'
        $csvFile = database_path('data/vendors.csv');

        if (!file_exists($csvFile)) {
            $this->command->error("File CSV tidak ditemukan di: $csvFile");
            return;
        }

        // Membuka file CSV
        $file = fopen($csvFile, 'r');
        
        // Lewati baris pertama (Header)
        $header = fgetcsv($file); 

        // Mapping Kolom CSV (berdasarkan urutan index):
        // 0: Kategori
        // 1: Detail (Masuk ke vendor_info)
        // 2: Vendor (Masuk ke name)
        // 3: PIC
        // 4: Kontak
        // 5: Email
        // 6: Alamat
        // 7: Respon Vendor
        // 8: Termin Pembayaran

        while (($row = fgetcsv($file)) !== false) {
            Vendor::create([
                'category'        => $row[0] ?? null,
                'vendor_info'     => $row[1] ?? null, // Detail
                'name'            => $row[2] ?? 'Vendor Tanpa Nama', // Nama Vendor
                'pic_name'        => $row[3] ?? null,
                'phone'           => $row[4] ?? null,
                'email'           => $row[5] ?? null,
                'address'         => $row[6] ?? null,
                'vendor_response' => $row[7] ?? null,
                'payment_terms'   => $row[8] ?? null,
            ]);
        }

        fclose($file);
    }
}