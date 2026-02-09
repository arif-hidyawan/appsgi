<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductDatabaseExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        // Load relasi agar tidak lambat (Eager Loading)
        return Product::with(['brand', 'category', 'vendor'])->get();
    }

    public function headings(): array
    {
        return [
            'Kode Item',
            'Nama Item',
            'Merek',
            'Kategori',
            'Vendor/Supplier Default',
        ];
    }

    public function map($product): array
    {
        return [
            $product->item_code,
            $product->name,
            $product->brand?->name ?? '-',
            $product->category?->name ?? '-',
            $product->vendor?->name ?? '-',
        ];
    }
}