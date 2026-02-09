<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company; // Pastikan Model Company ada
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Matikan pengecekan Foreign Key untuk performa & fleksibilitas
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // 2. Kosongkan tabel akun (Reset Total)
        Account::truncate();

        // 3. Ambil SEMUA ID Perusahaan
        // Jika belum ada perusahaan sama sekali, buat 1 Dummy Holding
        if (DB::table('companies')->count() == 0) {
            DB::table('companies')->insert([
                'code' => 'HOLDING', 
                'name' => 'Perusahaan Induk',
                'created_at' => now(), 
                'updated_at' => now()
            ]);
        }
        
        $companies = DB::table('companies')->get();

        // 4. DATA MASTER (TEMPLATE AKUN)
        // Kita simpan ID aslinya sebagai referensi 'old_id' untuk mapping nanti
        $masterAccounts = [
            ['old_id' => 1, 'code' => '1-1110', 'name' => 'KAS KECIL', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 60, 'is_cash_bank' => 1, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 2, 'code' => '1-1121', 'name' => 'BANK MANDIRI', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 60, 'is_cash_bank' => 1, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 3, 'code' => '1-1120', 'name' => 'BANK BCA', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 60, 'is_cash_bank' => 1, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 4, 'code' => '1-1130', 'name' => 'BANK BNI', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 60, 'is_cash_bank' => 1, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 5, 'code' => '1-1131', 'name' => 'SHOPEEPAY', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 60, 'is_cash_bank' => 1, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 6, 'code' => '1-1132', 'name' => 'DANA', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 60, 'is_cash_bank' => 1, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 9, 'code' => '1-2010', 'name' => 'PERSEDIAAN BARANG', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 70, 'is_cash_bank' => 0, 'is_inventory' => 1, 'is_active' => 1],
            ['old_id' => 10, 'code' => '1-2030', 'name' => 'CABANG A', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 70, 'is_cash_bank' => 0, 'is_inventory' => 1, 'is_active' => 1],
            ['old_id' => 11, 'code' => '1-2040', 'name' => 'CABANG C', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 70, 'is_cash_bank' => 0, 'is_inventory' => 1, 'is_active' => 1],
            ['old_id' => 12, 'code' => '1-2390', 'name' => 'POTONGAN BELI DAN BIAYA LAIN', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 70, 'is_cash_bank' => 0, 'is_inventory' => 1, 'is_active' => 1],
            ['old_id' => 13, 'code' => '4-0000', 'name' => 'PENDAPATAN', 'type' => 'H', 'nature' => 'revenue', 'parent_old_id' => null, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 14, 'code' => '4-1000', 'name' => 'PENDAPATAN DAGANG', 'type' => 'H', 'nature' => 'revenue', 'parent_old_id' => 13, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 15, 'code' => '4-1100', 'name' => 'PENDAPATAN JUAL', 'type' => 'D', 'nature' => 'revenue', 'parent_old_id' => 14, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 16, 'code' => '4-1101', 'name' => 'PENDAPATAN KONSINYASI', 'type' => 'D', 'nature' => 'revenue', 'parent_old_id' => 14, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 17, 'code' => '4-1500', 'name' => 'POTONGAN JUAL', 'type' => 'D', 'nature' => 'revenue', 'parent_old_id' => 14, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 18, 'code' => '4-1600', 'name' => 'RETUR JUAL', 'type' => 'D', 'nature' => 'revenue', 'parent_old_id' => 14, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 19, 'code' => '4-1700', 'name' => 'BIAYA', 'type' => 'D', 'nature' => 'revenue', 'parent_old_id' => 14, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 20, 'code' => '4-1800', 'name' => 'PEMASUKAN MODAL', 'type' => 'D', 'nature' => 'revenue', 'parent_old_id' => 14, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 21, 'code' => '4-2000', 'name' => 'PENDAPATAN JASA', 'type' => 'D', 'nature' => 'revenue', 'parent_old_id' => 13, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 22, 'code' => '6-0000', 'name' => 'BIAYA', 'type' => 'H', 'nature' => 'expense', 'parent_old_id' => null, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 23, 'code' => '6-1000', 'name' => 'BIAYA UMUM', 'type' => 'H', 'nature' => 'expense', 'parent_old_id' => 22, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 24, 'code' => '6-1100', 'name' => 'BIAYA LISTRIK/AIR/TELEPON', 'type' => 'D', 'nature' => 'expense', 'parent_old_id' => 23, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 25, 'code' => '6-1101', 'name' => 'BIAYA SEWA', 'type' => 'D', 'nature' => 'expense', 'parent_old_id' => 23, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 26, 'code' => '6-1102', 'name' => 'BIAYA ATK', 'type' => 'D', 'nature' => 'expense', 'parent_old_id' => 23, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 27, 'code' => '6-1103', 'name' => 'BIAYA BPJS KETENAGAKERJAAN', 'type' => 'D', 'nature' => 'expense', 'parent_old_id' => 23, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 28, 'code' => '6-1104', 'name' => 'BIAYA INTERNET', 'type' => 'D', 'nature' => 'expense', 'parent_old_id' => 23, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 29, 'code' => '6-1500', 'name' => 'KERUGIAN PIUTANG', 'type' => 'D', 'nature' => 'expense', 'parent_old_id' => 23, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 30, 'code' => '6-2001', 'name' => 'BIAYA IKLAN', 'type' => 'D', 'nature' => 'expense', 'parent_old_id' => 42, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 31, 'code' => '6-2002', 'name' => 'BIAYA PROMOSI', 'type' => 'D', 'nature' => 'expense', 'parent_old_id' => 42, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 32, 'code' => '6-3000', 'name' => 'BIAYA GAJI DAN UPAH', 'type' => 'H', 'nature' => 'expense', 'parent_old_id' => null, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 33, 'code' => '6-3001', 'name' => 'BIAYA GAJI PEGAWAI', 'type' => 'D', 'nature' => 'expense', 'parent_old_id' => 32, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 34, 'code' => '6-3010', 'name' => 'BIAYA UPAH KERJA', 'type' => 'D', 'nature' => 'expense', 'parent_old_id' => 32, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 35, 'code' => '6-3020', 'name' => 'BIAYA KOMISI', 'type' => 'D', 'nature' => 'expense', 'parent_old_id' => 32, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 36, 'code' => '6-4001', 'name' => 'BIAYA OPERASIONAL', 'type' => 'D', 'nature' => 'expense', 'parent_old_id' => 43, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 37, 'code' => '6-4002', 'name' => 'BIAYA BELI BAHAN BAKU', 'type' => 'D', 'nature' => 'expense', 'parent_old_id' => 43, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 38, 'code' => '6-5000', 'name' => 'BIAYA LAIN', 'type' => 'H', 'nature' => 'expense', 'parent_old_id' => null, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 39, 'code' => '6-5001', 'name' => 'BIAYA PENYUSUTAN', 'type' => 'D', 'nature' => 'expense', 'parent_old_id' => 38, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 40, 'code' => '6-9000', 'name' => 'BIAYA NON INVENTORY', 'type' => 'H', 'nature' => 'expense', 'parent_old_id' => null, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 41, 'code' => '6-9001', 'name' => 'BIAYA BELANJA NON INVENTORY', 'type' => 'D', 'nature' => 'expense', 'parent_old_id' => 40, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 42, 'code' => '6-2000', 'name' => 'BIAYA PEMASARAN', 'type' => 'H', 'nature' => 'expense', 'parent_old_id' => null, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 43, 'code' => '6-4000', 'name' => 'BIAYA OPERASIONAL', 'type' => 'H', 'nature' => 'expense', 'parent_old_id' => null, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 44, 'code' => '5-0000', 'name' => 'HPP', 'type' => 'H', 'nature' => 'cogs', 'parent_old_id' => null, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 45, 'code' => '5-1000', 'name' => 'HPP', 'type' => 'H', 'nature' => 'cogs', 'parent_old_id' => 44, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 46, 'code' => '5-1300', 'name' => 'HARGA POKOK PENJUALAN', 'type' => 'D', 'nature' => 'cogs', 'parent_old_id' => 45, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 47, 'code' => '5-1999', 'name' => 'POTONGAN PEMBELIAN', 'type' => 'D', 'nature' => 'cogs', 'parent_old_id' => 45, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 48, 'code' => '5-2000', 'name' => 'LAIN-LAIN', 'type' => 'H', 'nature' => 'cogs', 'parent_old_id' => 44, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 49, 'code' => '5-2100', 'name' => 'KERUGIAN PIUTANG', 'type' => 'D', 'nature' => 'cogs', 'parent_old_id' => 48, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 50, 'code' => '5-2200', 'name' => 'PENGATURAN STOK', 'type' => 'D', 'nature' => 'cogs', 'parent_old_id' => 48, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 51, 'code' => '5-2201', 'name' => 'ITEM MASUK', 'type' => 'D', 'nature' => 'cogs', 'parent_old_id' => 50, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 52, 'code' => '5-2202', 'name' => 'ITEM KELUAR', 'type' => 'D', 'nature' => 'cogs', 'parent_old_id' => 50, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 53, 'code' => '8-0000', 'name' => 'BIAYA LAIN', 'type' => 'H', 'nature' => 'other_expense', 'parent_old_id' => null, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 54, 'code' => '7-0000', 'name' => 'PENDAPATAN LAIN', 'type' => 'H', 'nature' => 'other_revenue', 'parent_old_id' => null, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 55, 'code' => '2-1100', 'name' => 'HUTANG USAHA', 'type' => 'H', 'nature' => 'liability', 'parent_old_id' => 81, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 56, 'code' => '1-1122', 'name' => 'BANK BRI', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 60, 'is_cash_bank' => 1, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 58, 'code' => '1-0000', 'name' => 'AKTIVA', 'type' => 'H', 'nature' => 'asset', 'parent_old_id' => null, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 59, 'code' => '1-1000', 'name' => 'AKTIVA LANCAR', 'type' => 'H', 'nature' => 'asset', 'parent_old_id' => 58, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 60, 'code' => '1-1100', 'name' => 'KAS & BANK', 'type' => 'H', 'nature' => 'asset', 'parent_old_id' => 59, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 61, 'code' => '1-1200', 'name' => 'PIUTANG', 'type' => 'H', 'nature' => 'asset', 'parent_old_id' => 59, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 62, 'code' => '1-1210', 'name' => 'PIUTANG USAHA', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 61, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 63, 'code' => '1-1211', 'name' => 'PIUTANG KONSINYASI', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 61, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 64, 'code' => '1-1212', 'name' => 'PIUTANG PPN', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 61, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 65, 'code' => '1-1220', 'name' => 'PIUTANG KARTU KREDIT', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 61, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 66, 'code' => '1-1299', 'name' => 'PIUTANG E-MONEY', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 61, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 67, 'code' => '1-1400', 'name' => 'PAJAK PEMBELIAN', 'type' => 'H', 'nature' => 'asset', 'parent_old_id' => 59, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 68, 'code' => '1-1410', 'name' => 'PPN MASUKAN', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 67, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 69, 'code' => '1-1421', 'name' => 'PAJAK DIBAYAR DIMUKA', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 67, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 70, 'code' => '1-2000', 'name' => 'PERSEDIAAN', 'type' => 'H', 'nature' => 'asset', 'parent_old_id' => 58, 'is_cash_bank' => 0, 'is_inventory' => 1, 'is_active' => 1],
            ['old_id' => 71, 'code' => '1-2035', 'name' => 'CABANG B', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 70, 'is_cash_bank' => 0, 'is_inventory' => 1, 'is_active' => 1],
            ['old_id' => 72, 'code' => '1-5000', 'name' => 'AKTIVA TETAP', 'type' => 'H', 'nature' => 'asset', 'parent_old_id' => 58, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 73, 'code' => '1-5100', 'name' => 'TANAH', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 72, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 74, 'code' => '1-5200', 'name' => 'BANGUNAN', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 72, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 75, 'code' => '1-5201', 'name' => 'AKUMULASI PENYUSUTAN BANGUNAN', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 72, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 76, 'code' => '1-5300', 'name' => 'KENDARAAN', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 72, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 77, 'code' => '1-5301', 'name' => 'AKUMULASI PENYUSUTAN KENDARAAN', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 72, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 78, 'code' => '1-5400', 'name' => 'PERALATAN', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 72, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 79, 'code' => '1-5401', 'name' => 'AKUMULASI PENYUSUTAN PERALATAN', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 72, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 80, 'code' => '2-0000', 'name' => 'KEWAJIBAN', 'type' => 'H', 'nature' => 'liability', 'parent_old_id' => null, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 81, 'code' => '2-1000', 'name' => 'KEWAJIBAN LANCAR', 'type' => 'H', 'nature' => 'liability', 'parent_old_id' => 80, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 82, 'code' => '2-2000', 'name' => 'HUTANG NON OPERASIONAL', 'type' => 'H', 'nature' => 'liability', 'parent_old_id' => 80, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 83, 'code' => '2-3000', 'name' => 'PENDAPATAN DITERIMA DIMUKA', 'type' => 'H', 'nature' => 'liability', 'parent_old_id' => 80, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 84, 'code' => '2-4000', 'name' => 'HUTANG PAJAK', 'type' => 'H', 'nature' => 'asset', 'parent_old_id' => 80, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 85, 'code' => '2-6000', 'name' => 'BARANG KONSINYASI MASUK', 'type' => 'H', 'nature' => 'liability', 'parent_old_id' => 80, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 86, 'code' => '2-1101', 'name' => 'HUTANG USAHA', 'type' => 'D', 'nature' => 'liability', 'parent_old_id' => 55, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 87, 'code' => '2-1130', 'name' => 'HUTANG KARTU KREDIT', 'type' => 'D', 'nature' => 'liability', 'parent_old_id' => 55, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 88, 'code' => '2-1140', 'name' => 'HUTANG KONSINYASI', 'type' => 'D', 'nature' => 'liability', 'parent_old_id' => 55, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 89, 'code' => '2-1200', 'name' => 'HUTANG GAJI', 'type' => 'D', 'nature' => 'asset', 'parent_old_id' => 81, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 90, 'code' => '2-1300', 'name' => 'HUTANG SALES', 'type' => 'D', 'nature' => 'liability', 'parent_old_id' => 81, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 91, 'code' => '2-4110', 'name' => 'PPN KELUARAN', 'type' => 'D', 'nature' => 'liability', 'parent_old_id' => 84, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 92, 'code' => '2-4120', 'name' => 'HUTANG PAJAK', 'type' => 'D', 'nature' => 'liability', 'parent_old_id' => 84, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 93, 'code' => '2-6100', 'name' => 'BARANG-BARANG KONSINYASI', 'type' => 'D', 'nature' => 'liability', 'parent_old_id' => 85, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 94, 'code' => '2-2100', 'name' => 'HUTANG TITIPAN ONGKIR', 'type' => 'D', 'nature' => 'liability', 'parent_old_id' => 82, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 95, 'code' => '3-0000', 'name' => 'MODAL', 'type' => 'H', 'nature' => 'equity', 'parent_old_id' => null, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 96, 'code' => '3-1000', 'name' => 'MODAL', 'type' => 'D', 'nature' => 'equity', 'parent_old_id' => 95, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 97, 'code' => '3-2000', 'name' => 'LABA DITAHAN', 'type' => 'D', 'nature' => 'equity', 'parent_old_id' => 95, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 98, 'code' => '3-3000', 'name' => 'LABA TAHUN BERJALAN', 'type' => 'D', 'nature' => 'equity', 'parent_old_id' => 95, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 99, 'code' => '3-8000', 'name' => 'PRIVE', 'type' => 'D', 'nature' => 'equity', 'parent_old_id' => 95, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 100, 'code' => '3-9000', 'name' => 'HISTORICAL BALANCING', 'type' => 'D', 'nature' => 'equity', 'parent_old_id' => 95, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 101, 'code' => '7-1000', 'name' => 'LABA SELISIH KURS', 'type' => 'D', 'nature' => 'other_revenue', 'parent_old_id' => 54, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 102, 'code' => '7-2000', 'name' => 'PENDAPATAN LAIN-LAIN', 'type' => 'D', 'nature' => 'other_revenue', 'parent_old_id' => 54, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 103, 'code' => '7-3000', 'name' => 'PENDAPTAN LAIN', 'type' => 'D', 'nature' => 'other_revenue', 'parent_old_id' => 54, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 104, 'code' => '7-4000', 'name' => 'KERINGANAN HUTANG', 'type' => 'D', 'nature' => 'other_revenue', 'parent_old_id' => 54, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
            ['old_id' => 105, 'code' => '8-1000', 'name' => 'BIAYA LAIN', 'type' => 'D', 'nature' => 'other_expense', 'parent_old_id' => 53, 'is_cash_bank' => 0, 'is_inventory' => 0, 'is_active' => 1],
        ];

        // 5. LOOP SEMUA PERUSAHAAN (Multi-Company)
        foreach ($companies as $company) {
            $this->command->info("Seeding accounts for Company: " . $company->name);
            
            // Map untuk menyimpan ID Lama -> ID Baru (untuk perusahaan ini)
            $idMapping = [];

            // A. INSERT SEMUA AKUN (Tanpa Parent Dulu)
            foreach ($masterAccounts as $acc) {
                // Hapus key helper yang tidak ada di DB
                $insertData = collect($acc)
                    ->except(['old_id', 'parent_old_id'])
                    ->merge(['company_id' => $company->id, 'parent_id' => null]) // Parent null dulu
                    ->toArray();
                
                // Insert & Dapatkan ID Baru (Auto Increment)
                $newId = Account::insertGetId($insertData);
                
                // Simpan mapping: ID Lama (misal 60) -> ID Baru (misal 215)
                $idMapping[$acc['old_id']] = $newId;
            }

            // B. UPDATE PARENT_ID (Linking Induk-Anak)
            foreach ($masterAccounts as $acc) {
                if ($acc['parent_old_id']) {
                    // Cari ID Baru milik akun ini
                    $currentNewId = $idMapping[$acc['old_id']];
                    
                    // Cari ID Baru milik Parent-nya
                    // (Jika parent ID 60 di map ke 215, maka pakai 215)
                    $parentNewId = $idMapping[$acc['parent_old_id']] ?? null;

                    if ($parentNewId) {
                        Account::where('id', $currentNewId)->update(['parent_id' => $parentNewId]);
                    }
                }
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}