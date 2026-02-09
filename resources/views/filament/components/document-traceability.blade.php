<div class="space-y-6 p-4">
    <div class="flex flex-col items-center justify-center space-y-2">
        
        {{-- ======================================================================== --}}
        {{-- 1. TAHAP RFQ (SUMBER) - MENDUKUNG BANYAK RFQ (MERGE) --}}
        {{-- ======================================================================== --}}
        
        @if($record->rfqs && $record->rfqs->count() > 0)
            {{-- CASE A: RFQ DARI RELASI BARU (MERGE/MANY) --}}
            <div class="flex flex-wrap gap-4 justify-center w-full">
                @foreach($record->rfqs as $rfq)
                    <div class="flex flex-col items-center group">
                        <a href="{{ \App\Filament\Resources\RfqResource::getUrl('edit', ['record' => $rfq->id]) }}" 
                           target="_blank"
                           class="flex flex-col items-center justify-center px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg shadow-sm hover:bg-gray-100 hover:border-gray-400 hover:shadow-md transition min-w-[200px] relative overflow-hidden">
                            
                            {{-- Label Kecil --}}
                            <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">RFQ Source</span>
                            
                            {{-- Nomor RFQ --}}
                            <div class="text-gray-700 font-bold text-lg leading-tight">{{ $rfq->rfq_number }}</div>
                            
                            {{-- Tanggal --}}
                            <div class="text-xs text-gray-400 mt-1">
                                {{ $rfq->date ? \Carbon\Carbon::parse($rfq->date)->format('d M Y') : '-' }}
                            </div>

                            {{-- Indicator Merge (Jika lebih dari 1) --}}
                            @if($record->rfqs->count() > 1)
                                <div class="absolute top-0 right-0 p-1">
                                    <div class="w-2 h-2 rounded-full bg-blue-400"></div>
                                </div>
                            @endif
                        </a>
                        
                        {{-- Garis konektor vertikal kecil untuk tiap item --}}
                        <div class="h-4 w-px bg-gray-300 group-last:hidden sm:hidden"></div>
                    </div>
                @endforeach
            </div>

            {{-- Konektor Panah Utama ke Quotation --}}
            <div class="flex flex-col items-center -mt-2">
                {{-- Garis Horizontal Penghubung jika banyak RFQ --}}
                @if($record->rfqs->count() > 1)
                    <div class="w-full max-w-[50%] border-t border-gray-300 mb-0"></div>
                @endif
                <div class="h-6 w-px bg-gray-300"></div>
                <div class="text-gray-400 -mt-1">↓</div>
            </div>

        @elseif($record->rfq_id) 
            {{-- CASE B: LEGACY DATA (DATA LAMA SEBELUM UPDATE SISTEM) --}}
             <div class="flex flex-col items-center">
                <a href="#" class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg shadow-sm text-center min-w-[200px]">
                    <span class="text-xs text-gray-500 font-semibold uppercase">Legacy RFQ</span>
                    <div class="text-gray-700 font-bold text-lg">RFQ #{{ $record->rfq_id }}</div>
                </a>
                <div class="h-6 w-px bg-gray-300 my-1"></div>
                <div class="text-gray-400">↓</div>
            </div>
        @else
            {{-- CASE C: DIRECT QUOTATION (TANPA RFQ) --}}
            <div class="flex flex-col items-center opacity-60">
                <div class="px-4 py-2 border border-dashed border-gray-300 rounded-lg text-gray-400 text-xs uppercase font-semibold">
                    Direct Quotation (Non-RFQ)
                </div>
                <div class="h-4 w-px bg-gray-300 my-1"></div>
                <div class="text-gray-400">↓</div>
            </div>
        @endif


        {{-- ======================================================================== --}}
        {{-- 2. TAHAP QUOTATION (POSISI SAAT INI) --}}
        {{-- ======================================================================== --}}
        
        <div class="flex flex-col items-center z-10">
            <div class="px-6 py-4 bg-white border-2 border-primary-500 rounded-xl shadow-lg text-center min-w-[240px] relative">

                <span class="text-xs text-primary-600 font-bold uppercase tracking-widest">Quotation</span>
                <div class="text-gray-900 font-black text-2xl my-1">{{ $record->quotation_number }}</div>
                <div class="text-xs text-gray-500 font-medium">{{ $record->date ? $record->date->format('d F Y') : '-' }}</div>
                
                {{-- Status Badge Dynamic --}}
                <div class="mt-3 pt-3 border-t border-gray-100">
                    @php
                        $color = match ($record->status) {
                            'Draft' => 'gray',
                            'Sent' => 'warning',
                            'Partial' => 'info',
                            'Accepted' => 'success',
                            'Rejected' => 'danger',
                            default => 'gray',
                        };
                        
                        $statusLabel = match ($record->status) {
                            'Sent' => 'Sent to Cust.',
                            'Partial' => 'Partial PO',
                            'Accepted' => 'Full PO',
                            default => $record->status,
                        };
                    @endphp
                    <span class="inline-flex items-center rounded-md bg-{{ $color }}-50 px-2.5 py-1 text-xs font-medium text-{{ $color }}-700 ring-1 ring-inset ring-{{ $color }}-600/20">
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>
        </div>


        {{-- ======================================================================== --}}
        {{-- 3. TAHAP SALES ORDER (DESTINATION) - MENDUKUNG BANYAK SO --}}
        {{-- ======================================================================== --}}

        @php
            // Ambil semua SO yang terhubung ke Quotation ini
            $salesOrders = \App\Models\SalesOrder::where('quotation_id', $record->id)->get();
        @endphp

        @if($salesOrders->count() > 0)
            {{-- Konektor Panah --}}
            <div class="flex flex-col items-center -mb-2">
                <div class="text-gray-400">↓</div>
                <div class="h-6 w-px bg-gray-300"></div>
                @if($salesOrders->count() > 1)
                    <div class="w-full max-w-[50%] border-t border-gray-300 mb-2"></div>
                @endif
            </div>

            <div class="flex flex-wrap gap-4 justify-center w-full pt-2">
                @foreach($salesOrders as $so)
                    <div class="flex flex-col items-center">
                        <a href="{{ \App\Filament\Resources\SalesOrderResource::getUrl('edit', ['record' => $so->id]) }}" 
                           target="_blank"
                           class="flex flex-col items-center justify-center px-4 py-3 bg-green-50 border border-green-300 rounded-lg shadow-sm hover:bg-green-100 hover:border-green-400 hover:shadow-md transition min-w-[200px]">
                            
                            <span class="text-[10px] text-green-700 font-bold uppercase tracking-wider mb-1">Sales Order</span>
                            
                            <div class="text-green-800 font-bold text-lg leading-tight">{{ $so->so_number }}</div>
                            
                            <div class="text-[10px] text-green-600 mt-1 bg-green-200/50 px-2 py-0.5 rounded">
                                PO: {{ $so->customer_po_number ?? '-' }}
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

        @elseif($record->status === 'Rejected')
            {{-- CASE D: REJECTED (STOP) --}}
            <div class="flex flex-col items-center">
                <div class="text-red-400">↓</div>
                <div class="h-6 w-px bg-red-300 my-1"></div>
                <div class="px-4 py-2 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm font-semibold">
                    ⛔ Dibatalkan / Ditolak
                </div>
            </div>

        @else
            {{-- CASE E: MENUNGGU SO --}}
            <div class="flex flex-col items-center opacity-50">
                <div class="text-gray-400">↓</div>
                <div class="h-8 w-px bg-gray-300 my-1"></div>
                <div class="px-4 py-2 border border-dashed border-gray-400 rounded-lg text-gray-500 text-sm italic">
                    Menunggu Sales Order...
                </div>
            </div>
        @endif

    </div>
</div>