<div class="space-y-6 p-4">
    <div class="flex flex-col items-center justify-center space-y-4">

        {{-- ================================================= --}}
        {{-- BAGIAN 1: SUMBER DOKUMEN (RFQS & QUOTATIONS)      --}}
        {{-- ================================================= --}}

        {{-- Cek apakah SO ini punya Quotations (Many-to-Many) --}}
        @if($record->quotations && $record->quotations->count() > 0)
            
            {{-- PERBAIKAN 1: Tambahkan 'items-end' pada container utama agar sejajar bawah --}}
            <div class="flex flex-wrap justify-center items-end gap-6 w-full">
                @foreach($record->quotations as $quotation)
                    
                    {{-- PERBAIKAN 2: Gunakan 'justify-end' agar Quotation selalu di posisi paling bawah kolom --}}
                    <div class="flex flex-col items-center justify-end gap-2 group h-full">
                        
                        {{-- TAHAP 1.A: RFQ ASAL DARI QUOTATION INI --}}
                        @if($quotation->rfqs && $quotation->rfqs->count() > 0)
                            <div class="flex flex-col items-center gap-1 mb-1">
                                <div class="flex flex-wrap justify-center gap-2">
                                    @foreach($quotation->rfqs as $rfq)
                                        <a href="{{ \App\Filament\Resources\RfqResource::getUrl('edit', ['record' => $rfq->id]) }}" 
                                           target="_blank"
                                           class="px-3 py-1.5 bg-gray-50 border border-gray-200 rounded shadow-sm hover:bg-gray-100 text-center min-w-[120px] transition opacity-75 hover:opacity-100">
                                            <span class="text-[10px] text-gray-400 font-bold uppercase block">Source RFQ</span>
                                            <div class="text-gray-600 font-bold text-xs">{{ $rfq->rfq_number }}</div>
                                        </a>
                                    @endforeach
                                </div>
                                <div class="h-4 w-px bg-gray-300"></div>
                                <div class="text-gray-300 text-xs">↓</div>
                            </div>
                        @else
                            {{-- Spacer opsional jika ingin benar-benar rata atas (kosongkan jika ingin rata bawah natural) --}}
                            {{-- <div class="flex-grow"></div> --}}
                        @endif

                        {{-- TAHAP 1.B: QUOTATION --}}
                        <a href="{{ \App\Filament\Resources\QuotationResource::getUrl('edit', ['record' => $quotation->id]) }}" 
                           target="_blank"
                           class="px-4 py-2 bg-yellow-50 border border-yellow-300 rounded-lg shadow-sm hover:bg-yellow-100 transition text-center min-w-[180px] relative z-10">
                            <span class="text-[10px] text-yellow-600 font-bold uppercase block tracking-wide">Quotation Source</span>
                            <div class="text-yellow-800 font-bold text-base">{{ $quotation->quotation_number }}</div>
                            <div class="text-[10px] text-yellow-600/70">{{ $quotation->date ? $quotation->date->format('d M Y') : '-' }}</div>
                            
                            {{-- Indicator Merge (Jika lebih dari 1 Quote) --}}
                            @if($record->quotations->count() > 1)
                                <div class="absolute -top-1 -right-1">
                                    <span class="flex h-3 w-3">
                                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                      <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                                    </span>
                                </div>
                            @endif
                        </a>
                        
                        {{-- Garis konektor vertikal untuk tiap quote --}}
                        <div class="h-4 w-px bg-gray-300 sm:hidden"></div>
                    </div>
                @endforeach
            </div>

            {{-- Konektor Gabungan ke SO --}}
            <div class="flex flex-col items-center mt-[-8px]">
                {{-- Garis Horizontal jika banyak Quote --}}
                @if($record->quotations->count() > 1)
                    <div class="w-full max-w-[60%] border-t border-gray-300 mb-0 h-px"></div>
                @endif
                <div class="h-6 w-px bg-gray-300"></div>
                <div class="text-gray-400 -mt-1">↓</div>
            </div>

        @elseif($record->quotation_id) 
            {{-- FALLBACK LEGACY DATA (SINGLE COLUMN) --}}
             <div class="flex flex-col items-center">
                <a href="#" class="px-4 py-2 bg-yellow-50 border border-yellow-300 rounded-lg shadow-sm text-center min-w-[200px]">
                    <span class="text-xs text-yellow-600 font-semibold uppercase">Legacy Quotation</span>
                    <div class="text-yellow-800 font-bold text-lg">QT #{{ $record->quotation_id }}</div>
                </a>
                <div class="h-6 w-px bg-gray-300 my-1"></div>
                <div class="text-gray-400">↓</div>
            </div>
        @else
            {{-- DIRECT SALES ORDER --}}
            <div class="flex flex-col items-center opacity-60">
                <div class="px-4 py-2 border border-dashed border-gray-300 rounded-lg text-gray-400 text-xs uppercase font-semibold">
                    Direct Order (Tanpa Quotation)
                </div>
                <div class="h-6 w-px bg-gray-300 my-1"></div>
                <div class="text-gray-400">↓</div>
            </div>
        @endif


        {{-- ================================================= --}}
        {{-- BAGIAN 2: DOKUMEN SAAT INI (SALES ORDER)          --}}
        {{-- ================================================= --}}

        <div class="flex flex-col items-center z-10">
            <div class="px-6 py-4 bg-white border-2 border-primary-500 rounded-xl shadow-lg text-center min-w-[240px] relative">
          
                
                <span class="text-xs text-primary-600 font-bold uppercase tracking-widest">Sales Order</span>
                <div class="text-gray-900 font-black text-2xl my-1">{{ $record->so_number }}</div>
                <div class="text-xs text-gray-500 font-medium">{{ $record->date ? $record->date->format('d F Y') : '-' }}</div>
                
                <div class="mt-3 pt-3 border-t border-gray-100">
                    @php
                        $color = match ($record->status) {
                            'New' => 'info',
                            'Processed' => 'warning',
                            'Siap Kirim' => 'success',
                            'Completed' => 'primary',
                            'Invoiced' => 'success',
                            'Paid' => 'success',
                            'Cancelled' => 'danger',
                            default => 'gray',
                        };
                    @endphp
                    <span class="inline-flex items-center rounded-md bg-{{ $color }}-50 px-2.5 py-1 text-xs font-medium text-{{ $color }}-700 ring-1 ring-inset ring-{{ $color }}-600/20">
                        {{ $record->status }}
                    </span>
                </div>
            </div>
        </div>


        {{-- ================================================= --}}
        {{-- BAGIAN 3: TURUNAN (PO, DO, INVOICE)               --}}
        {{-- ================================================= --}}

        {{-- Panah Cabang --}}
        <div class="flex flex-col items-center">
            <div class="text-gray-400">↓</div>
            <div class="h-6 w-px bg-gray-300"></div>
        </div>

        {{-- Grid 3 Kolom untuk Dokumen Hilir --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full pt-4 border-t border-gray-200">
            
            {{-- KOLOM 1: Purchase Orders --}}
            <div class="flex flex-col items-center gap-3">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">
                    <x-heroicon-o-shopping-cart class="w-4 h-4"/> Purchase Orders
                </div>
                @forelse($record->purchaseOrders as $po)
                    <a href="{{ \App\Filament\Resources\PurchaseOrderResource::getUrl('edit', ['record' => $po->id]) }}" 
                       target="_blank"
                       class="w-full px-4 py-3 bg-orange-50 border border-orange-200 rounded-lg shadow-sm hover:bg-orange-100 hover:border-orange-300 transition group relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-orange-400"></div>
                        <span class="text-[10px] text-orange-600 font-bold uppercase block mb-0.5">Ke Vendor</span>
                        <div class="text-orange-900 font-bold text-sm">{{ $po->po_number }}</div>
                        <div class="text-[10px] text-gray-500 truncate">{{ $po->vendor->name ?? '-' }}</div>
                    </a>
                @empty
                    <div class="w-full px-4 py-3 border border-dashed border-gray-200 rounded-lg text-center">
                        <span class="text-xs text-gray-300 italic">- Belum ada PO -</span>
                    </div>
                @endforelse
            </div>

            {{-- KOLOM 2: Delivery Orders --}}
            <div class="flex flex-col items-center gap-3 border-l border-r border-gray-100 md:px-4">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">
                    <x-heroicon-o-truck class="w-4 h-4"/> Delivery Orders
                </div>
                @forelse($record->deliveryOrders as $do)
                    <a href="{{ \App\Filament\Resources\DeliveryOrderResource::getUrl('edit', ['record' => $do->id]) }}" 
                       target="_blank"
                       class="w-full px-4 py-3 bg-green-50 border border-green-200 rounded-lg shadow-sm hover:bg-green-100 hover:border-green-300 transition group relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-green-500"></div>
                        <span class="text-[10px] text-green-700 font-bold uppercase block mb-0.5">Surat Jalan</span>
                        <div class="text-green-900 font-bold text-sm">{{ $do->do_number }}</div>
                        <div class="text-[10px] text-green-600/80">{{ $do->date ? $do->date->format('d M Y') : '-' }}</div>
                    </a>
                @empty
                    <div class="w-full px-4 py-3 border border-dashed border-gray-200 rounded-lg text-center">
                        <span class="text-xs text-gray-300 italic">- Belum dikirim -</span>
                    </div>
                @endforelse
            </div>

            {{-- KOLOM 3: Invoices --}}
            <div class="flex flex-col items-center gap-3">
                <div class="flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">
                    <x-heroicon-o-document-currency-dollar class="w-4 h-4"/> Invoices
                </div>
                @forelse($record->salesInvoices as $inv)
                    <a href="{{ \App\Filament\Resources\SalesInvoiceResource::getUrl('edit', ['record' => $inv->id]) }}" 
                       target="_blank"
                       class="w-full px-4 py-3 bg-red-50 border border-red-200 rounded-lg shadow-sm hover:bg-red-100 hover:border-red-300 transition group relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-red-500"></div>
                        <span class="text-[10px] text-red-600 font-bold uppercase block mb-0.5">Tagihan</span>
                        <div class="text-red-900 font-bold text-sm">{{ $inv->invoice_number }}</div>
                        
                        @php
                            $statusColor = $inv->status === 'Paid' ? 'text-green-600 bg-green-100' : 'text-red-600 bg-red-100';
                        @endphp
                        <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[10px] font-bold {{ $statusColor }}">
                            {{ $inv->status }}
                        </span>
                    </a>
                @empty
                    <div class="w-full px-4 py-3 border border-dashed border-gray-200 rounded-lg text-center">
                        <span class="text-xs text-gray-300 italic">- Belum ditagih -</span>
                    </div>
                @endforelse
            </div>

        </div>

    </div>
</div>