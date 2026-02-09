<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\RfqItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Filament\Notifications\Notification;

class RfqItemsImport implements ToCollection, WithHeadingRow
{
    protected $rfqId;

    public function __construct($rfqId)
    {
        $this->rfqId = $rfqId;
    }

    public function collection(Collection $rows)
    {
        $count = 0;
        foreach ($rows as $row) {
            // Cari Produk berdasarkan Item Code
            $product = Product::where('item_code', $row['item_code'])->first();

            if ($product) {
                // Cek apakah item sudah ada di RFQ ini (agar tidak duplikat)
                $exists = RfqItem::where('rfq_id', $this->rfqId)
                    ->where('product_id', $product->id)
                    ->exists();

                if (!$exists) {
                    RfqItem::create([
                        'rfq_id' => $this->rfqId,
                        'product_id' => $product->id,
                        'qty' => $row['qty'] ?? 1,
                        'notes' => $row['notes'] ?? null,
                        // Default value lain bisa diset di sini (misal harga 0)
                        'cost_price' => 0,
                        'selling_price' => 0,
                    ]);
                    $count++;
                }
            }
        }

        if ($count > 0) {
            Notification::make()
                ->title("Berhasil mengimpor $count item")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Tidak ada item baru yang diimpor (Cek Kode Barang)')
                ->warning()
                ->send();
        }
    }
}