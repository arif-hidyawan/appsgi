<div class="space-y-4">
    <div class="flex flex-col items-center justify-center space-y-4">

        {{-- ================================================= --}}
        {{-- BAGIAN 1: HULU (RFQ -> QUOTATION -> SO)           --}}
        {{-- ================================================= --}}

        {{-- 1. RFQ (Jika Ada) --}}
        @php
            $quotation = $record->salesOrder?->quotation;
            $rfq = $quotation?->rfq;
        @endphp

        @if($rfq)
            <div class="flex flex-col items-center">
                <a href="{{ \App\Filament\Resources\RfqResource::getUrl('edit', ['record' => $rfq->id]) }}" 
                   target="_blank"
                   class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg shadow-sm hover:bg-gray-200 transition text-center min-w-[200px]">
                    <span class="text-xs text-gray-500 font-semibold uppercase">RFQ (Request For Quotation)</span>
                    <div class="text-gray-700 font-bold text-lg">{{ $rfq->rfq_number }}</div>
                    <div class="text-xs text-gray-400">{{ $rfq->date->format('d M Y') }}</div>
                </a>
                <div class="h-8 w-px bg-gray-300 my-1"></div>
                <div class="text-gray-400">↓</div>
            </div>
        @endif

        {{-- 2. QUOTATION (Jika Ada) --}}
        @if($quotation)
            <div class="flex flex-col items-center">
                <a href="{{ \App\Filament\Resources\QuotationResource::getUrl('edit', ['record' => $quotation->id]) }}" 
                   target="_blank"
                   class="px-4 py-2 bg-yellow-50 border border-yellow-300 rounded-lg shadow-sm hover:bg-yellow-100 transition text-center min-w-[200px]">
                    <span class="text-xs text-yellow-600 font-semibold uppercase">Quotation (Penawaran)</span>
                    <div class="text-yellow-800 font-bold text-lg">{{ $quotation->quotation_number }}</div>
                    <div class="text-xs text-gray-500">{{ $quotation->date->format('d M Y') }}</div>
                </a>
                <div class="h-8 w-px bg-gray-300 my-1"></div>
                <div class="text-gray-400">↓</div>
            </div>
        @endif

        {{-- 3. SALES ORDER (Jika Ada) --}}
        @if($record->salesOrder)
            <div class="flex flex-col items-center">
                <a href="{{ \App\Filament\Resources\SalesOrderResource::getUrl('edit', ['record' => $record->sales_order_id]) }}" 
                   target="_blank"
                   class="px-4 py-2 bg-blue-50 border border-blue-300 rounded-lg shadow-sm hover:bg-blue-100 transition text-center min-w-[200px]">
                    <span class="text-xs text-blue-600 font-semibold uppercase">Sales Order (Pesanan)</span>
                    <div class="text-blue-800 font-bold text-lg">{{ $record->salesOrder->so_number }}</div>
                    <div class="text-xs text-gray-500">PO Cust: {{ $record->salesOrder->customer_po_number }}</div>
                </a>
                <div class="h-8 w-px bg-gray-300 my-1"></div>
                <div class="text-gray-400">↓</div>
            </div>
        @endif

        {{-- ================================================= --}}
        {{-- BAGIAN 2: DOKUMEN SAAT INI (PURCHASE ORDER)       --}}
        {{-- ================================================= --}}

        <div class="flex flex-col items-center">
            <div class="px-4 py-3 bg-orange-50 border-2 border-orange-500 rounded-lg shadow-md text-center min-w-[220px]">
                <span class="text-xs text-orange-600 font-semibold uppercase font-bold">Purchase Order (Current)</span>
                <div class="text-gray-900 font-bold text-xl">{{ $record->po_number }}</div>
                <div class="text-xs text-gray-500">{{ $record->date->format('d M Y') }}</div>
                <div class="mt-2 text-xs font-bold text-gray-700 uppercase">Vendor: {{ $record->vendor->name ?? '-' }}</div>
                <div class="mt-1">
                    <span class="inline-flex items-center rounded-md bg-white px-2 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-700/10 uppercase">
                        {{ $record->status }}
                    </span>
                </div>
            </div>
        </div>

        {{-- ================================================= --}}
        {{-- BAGIAN 3: TURUNAN (GR & PURCHASE INVOICE)         --}}
        {{-- ================================================= --}}

        {{-- Panah Cabang --}}
        <div class="h-8 w-px bg-gray-300 my-1"></div>
        <div class="text-gray-400">↓</div>

        {{-- Grid 2 Kolom untuk Dokumen Hilir --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full pt-2 border-t border-gray-200">
            
            {{-- KOLOM 1: Goods Receive --}}
            <div class="flex flex-col items-center gap-2">
                <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Goods Receive</div>
                @php $grs = \App\Models\GoodsReceive::where('purchase_order_id', $record->id)->get(); @endphp
                @forelse($grs as $gr)
                    <a href="{{ \App\Filament\Resources\GoodsReceiveResource::getUrl('edit', ['record' => $gr->id]) }}" 
                       target="_blank"
                       class="w-full px-3 py-2 bg-green-50 border border-green-200 rounded-lg shadow-sm hover:bg-green-100 text-center transition">
                        <span class="text-[10px] text-green-600 font-semibold uppercase block">Penerimaan Barang</span>
                        <div class="text-green-800 font-bold text-sm">{{ $gr->gr_number }}</div>
                        <div class="text-[10px] text-gray-500">Tgl: {{ $gr->date->format('d M Y') }}</div>
                    </a>
                @empty
                    <span class="text-xs text-gray-300 italic py-2">- Belum Diterima -</span>
                @endforelse
            </div>

            {{-- KOLOM 2: Purchase Invoices --}}
            <div class="flex flex-col items-center gap-2 border-l border-gray-100 px-2">
                <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Purchase Invoice</div>
                @php $invs = \App\Models\PurchaseInvoice::where('purchase_order_id', $record->id)->get(); @endphp
                @forelse($invs as $inv)
                    <a href="{{ \App\Filament\Resources\PurchaseInvoiceResource::getUrl('edit', ['record' => $inv->id]) }}" 
                       target="_blank"
                       class="w-full px-3 py-2 bg-red-50 border border-red-200 rounded-lg shadow-sm hover:bg-red-100 text-center transition">
                        <span class="text-[10px] text-red-600 font-semibold uppercase block">Tagihan Vendor</span>
                        <div class="text-red-800 font-bold text-sm">{{ $inv->invoice_number }}</div>
                        <div class="text-[10px] text-gray-500">Status: {{ $inv->status }}</div>
                    </a>
                @empty
                    <span class="text-xs text-gray-300 italic py-2">- Belum Ditagih -</span>
                @endforelse
            </div>

        </div>

    </div>
</div>