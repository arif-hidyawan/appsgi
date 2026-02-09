<div class="space-y-4">
    @if($stocks->isEmpty())
        <div class="text-center text-gray-500 py-4">
            Tidak ada stok tersedia di gudang manapun.
        </div>
    @else
        {{-- Header Modal --}}
        <div class="p-3 border rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-700 flex justify-between items-center">
            <div>
                <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Produk:</span>
                <span class="text-sm font-bold dark:text-white">{{ $product->name }}</span>
            </div>
            <div class="flex items-center gap-2">
                <x-filament::badge color="gray">
                    Qty Order: {{ $orderItem->qty }}
                </x-filament::badge>
                
                @if($orderItem->reserved_at)
                    <x-filament::badge color="success" icon="heroicon-m-lock-closed">
                        Status: Terkunci
                    </x-filament::badge>
                @endif
            </div>
        </div>

        {{-- Tabel Stok --}}
        <div class="overflow-x-auto border border-gray-200 rounded-lg dark:border-gray-700">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Perusahaan</th>
                        <th class="px-4 py-3">Gudang</th>
                        <th class="px-4 py-3 text-center">Stok Fisik</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($stocks as $stock)
    @php
        // PERBAIKAN DISINI:
        // Hanya hitung Reserved jika SO-nya MASIH AKTIF (Belum Completed/Dikirim & Tidak Cancel)
        $reservedQty = \App\Models\SalesOrderItem::where('product_id', $stock->product_id)
            ->where('warehouse_id', $stock->warehouse_id)
            ->whereNotNull('reserved_at')
            ->whereHas('salesOrder', function($q) {
                // Filter status SO yang memegang stok
                // Abaikan 'Completed' karena asumsinya stok fisik sudah dipotong saat DO
                // Abaikan 'Cancelled' karena batal
                $q->whereNotIn('status', ['Completed', 'Cancelled']); 
            })
            ->sum('qty');

        // Stok Bebas = Stok Fisik - Stok Booking Aktif
        $availableQty = $stock->quantity - $reservedQty;
    @endphp

    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 {{ $stock->company_id == $currentCompanyId ? 'bg-primary-50/50' : '' }}">
        
        {{-- Kolom Perusahaan & Gudang (TIDAK BERUBAH) --}}
        <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">
            {{ $stock->company->name }}
            @if($stock->company_id == $currentCompanyId)
                <x-filament::badge size="xs" color="primary">Unit Anda</x-filament::badge>
            @endif
        </td>
        <td class="px-4 py-2 italic text-gray-400">
            {{ $stock->warehouse->name }}
        </td>

        {{-- UPDATE KOLOM STOK FISIK --}}
        <td class="px-4 py-2 text-center text-gray-700 dark:text-gray-200">
            <div>
                <span class="font-bold">{{ number_format($stock->quantity, 0, ',', '.') }}</span>
                <span class="text-xs text-gray-400 block">Fisik</span>
            </div>
            
            {{-- Tampilkan Info Jika Ada yang Reserved --}}
            @if($reservedQty > 0)
                <div class="text-xs text-danger-600 font-medium mt-1">
                    (-{{ $reservedQty }} Booked)
                </div>
                <div class="text-xs text-success-600 font-bold border-t mt-1 pt-1">
                    {{ $availableQty }} Bebas
                </div>
            @endif
        </td>

        <td class="px-4 py-2 text-center">
            @if($stock->company_id != $currentCompanyId)
                {{-- Tombol Mutasi (TIDAK BERUBAH) --}}
                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\Resources\StockTransferResource::getUrl('create', [
                        'source_company_id' => $stock->company_id,
                        'source_warehouse_id' => $stock->warehouse_id,
                        'destination_company_id' => $currentCompanyId,
                        'product_id' => $stock->product_id,
                        'qty' => $orderItem->qty ?? 1
                    ]) }}"
                    target="_blank" size="xs" color="warning" icon="heroicon-m-arrows-right-left">
                    Mutasi
                </x-filament::button>
            @else
                {{-- LOGIKA UTAMA --}}
                @if($orderItem->reserved_at)
                    @if($orderItem->warehouse_id == $stock->warehouse_id)
                        <x-filament::button size="xs" color="success" icon="heroicon-m-lock-closed" disabled class="opacity-100">
                            Terkunci: {{ $orderItem->qty }}
                        </x-filament::button>
                    @else
                        <span class="text-xs text-gray-400 italic">Disable</span>
                    @endif
                @else
                    {{-- CEK STOK BEBAS (AVAILABLE), BUKAN STOK FISIK --}}
                    @if($availableQty >= $orderItem->qty)
                        <x-filament::button
                            wire:click="reserveStock({{ $orderItem->id }}, {{ $stock->warehouse_id }})"
                            wire:loading.attr="disabled"
                            size="xs" color="primary" icon="heroicon-m-lock-closed">
                            Kunci Stok
                        </x-filament::button>
                    @else
                        {{-- Jika Stok Fisik Ada, Tapi Stok Bebas Habis --}}
                        @if($stock->quantity >= $orderItem->qty)
                            <x-filament::badge color="danger">
                                Sudah Dibooking
                            </x-filament::badge>
                        @else
                            <x-filament::badge color="danger">
                                Kurang ({{ $orderItem->qty - $availableQty }})
                            </x-filament::badge>
                        @endif
                    @endif
                @endif
            @endif
        </td>
    </tr>
@endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-900/50 font-bold text-gray-900 dark:text-white">
                    <tr>
                        <td colspan="2" class="px-4 py-3 text-right uppercase tracking-wider text-xs text-gray-500">
                            Total Stok Gabungan 
                        </td>
                        <td class="px-4 py-3 text-center text-lg text-primary-600 dark:text-primary-400 border-l border-gray-200 dark:border-gray-700">
                            {{ number_format($stocks->sum('quantity'), 0, ',', '.') }}
                        </td>
                        <td class="bg-gray-100 dark:bg-gray-800"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        @if($orderItem->reserved_at)
            <div class="mt-2 text-right">
                <p class="text-xs text-warning-600 italic">
                    <span class="font-bold">Info:</span> Item ini sudah dikunci. Untuk mengubah gudang, harap "Lepas Kunci" terlebih dahulu di tabel utama.
                </p>
            </div>
        @else
            <div class="mt-2 text-right">
                <p class="text-xs text-gray-500 italic">
                    *Mengunci stok akan mereservasi barang di gudang terpilih untuk Sales Order ini saja.
                </p>
            </div>
        @endif
    @endif
</div>