<?php

namespace App\Filament\Resources\StockTransferResource\Pages;

use App\Filament\Resources\StockTransferResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStockTransfer extends CreateRecord
{
    protected static string $resource = StockTransferResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    public function mount(): void
    {
        parent::mount();

        // Mengisi data awal dari URL
        if (request()->has('source_company_id')) {
            $this->form->fill([
                'source_company_id'      => request('source_company_id'),
                'source_warehouse_id'    => request('source_warehouse_id'),
                'destination_company_id' => request('destination_company_id'),
                'date'                   => now(),
            ]);
        }
    }

    // --- LOGIC AUTO-ADD ITEM DARI URL ---
    protected function afterCreate(): void
    {
        if (request()->has('product_id')) {
            $this->record->items()->create([
                'product_id' => request('product_id'),
                'qty'        => request('qty', 1),
            ]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
{
    // Memastikan jika transfer_number kosong karena masalah UI, kita isi di level code
    if (empty($data['transfer_number'])) {
        $data['transfer_number'] = 'TRF-' . now()->format('Ymd') . '-' . rand(100, 999);
    }
    
    // Memastikan status selalu Draft saat baru dibuat
    $data['status'] = 'Draft';

    return $data;
}
}