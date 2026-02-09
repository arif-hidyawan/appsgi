<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;

class RfqItemTemplateExport implements WithHeadings
{
    public function headings(): array
    {
        return [
            'item_code', // Kode Barang (Wajib sama dengan database)
            'qty',       // Jumlah
            'notes',     // Catatan (Opsional)
        ];
    }
}