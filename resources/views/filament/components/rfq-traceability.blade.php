<div class="space-y-6 p-4">
    <div class="flex flex-col items-center justify-center space-y-4">

        {{-- ================================================= --}}
        {{-- BAGIAN 1: RFQ (CURRENT RECORD)                    --}}
        {{-- ================================================= --}}

        <div class="flex flex-col items-center z-10">
            <div class="px-6 py-4 bg-white border-2 border-primary-500 rounded-xl shadow-lg text-center min-w-[240px] relative">
      
                <span class="text-xs text-primary-600 font-bold uppercase tracking-widest">Request For Quotation</span>
                <div class="text-gray-900 font-black text-2xl my-1">{{ $record->rfq_number }}</div>
                <div class="text-xs text-gray-500 font-medium">{{ $record->date ? $record->date->format('d F Y') : '-' }}</div>
                
                <div class="mt-3 pt-3 border-t border-gray-100">
                    @php
                        $color = match ($record->status) {
                            'Draft' => 'gray',
                            'Partial' => 'warning',
                            'Selesai' => 'success',
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
        {{-- BAGIAN 2: DOWNSTREAM (QUOTATIONS -> SALES ORDERS) --}}
        {{-- ================================================= --}}

        @if($record->quotations && $record->quotations->count() > 0)
            
            {{-- Panah Turun --}}
            <div class="flex flex-col items-center">
                <div class="text-gray-400">↓</div>
                <div class="h-6 w-px bg-gray-300"></div>
            </div>

            {{-- Loop Quotations --}}
            <div class="flex flex-wrap justify-center items-start gap-8 w-full border-t border-gray-200 pt-6">
                @foreach($record->quotations as $quotation)
                    <div class="flex flex-col items-center gap-4 group">
                        
                        {{-- KARTU QUOTATION --}}
                        <a href="{{ \App\Filament\Resources\QuotationResource::getUrl('edit', ['record' => $quotation->id]) }}" 
                           target="_blank"
                           class="flex flex-col items-center justify-center px-4 py-3 bg-yellow-50 border border-yellow-300 rounded-lg shadow-sm hover:bg-yellow-100 hover:border-yellow-400 transition min-w-[200px] relative">
                            
                            <span class="text-[10px] text-yellow-600 font-bold uppercase tracking-wider mb-1">Quotation</span>
                            <div class="text-yellow-900 font-bold text-lg leading-tight">{{ $quotation->quotation_number }}</div>
                            <div class="text-[10px] text-yellow-600/80">{{ $quotation->date ? $quotation->date->format('d M Y') : '-' }}</div>
                            
                            {{-- Status Quote --}}
                            <div class="mt-1">
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-yellow-200/50 text-yellow-800 font-semibold border border-yellow-200">
                                    {{ $quotation->status }}
                                </span>
                            </div>
                        </a>

                        {{-- SALES ORDERS (NESTED DARI QUOTATION) --}}
                        @if($quotation->salesOrders && $quotation->salesOrders->count() > 0)
                            <div class="flex flex-col items-center w-full">
                                <div class="h-4 w-px bg-gray-300"></div>
                                <div class="text-gray-300 text-xs mb-1">↓</div>
                                
                                <div class="flex flex-col gap-2 w-full">
                                    @foreach($quotation->salesOrders as $so)
                                        <a href="{{ \App\Filament\Resources\SalesOrderResource::getUrl('edit', ['record' => $so->id]) }}" 
                                           target="_blank"
                                           class="flex items-center justify-between gap-2 px-3 py-2 bg-green-50 border border-green-200 rounded shadow-sm hover:bg-green-100 transition w-full">
                                            
                                            <div class="flex flex-col text-left">
                                                <span class="text-[9px] text-green-600 font-bold uppercase">Sales Order</span>
                                                <span class="text-xs font-bold text-green-800">{{ $so->so_number }}</span>
                                            </div>
                                            
                                            <x-heroicon-o-chevron-right class="w-3 h-3 text-green-400"/>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @elseif($quotation->status === 'Rejected')
                             <div class="flex flex-col items-center opacity-60">
                                <div class="h-4 w-px bg-red-300"></div>
                                <span class="text-[10px] text-red-500 font-bold mt-1">Ditolak</span>
                            </div>
                        @else
                            <div class="flex flex-col items-center opacity-40">
                                <div class="h-4 w-px bg-gray-300 border-dashed"></div>
                                <span class="text-[10px] text-gray-400 italic mt-1">Menunggu SO</span>
                            </div>
                        @endif

                    </div>
                @endforeach
            </div>

        @else
            {{-- JIKA BELUM ADA QUOTATION --}}
            <div class="flex flex-col items-center opacity-50 mt-2">
                <div class="text-gray-400">↓</div>
                <div class="h-8 w-px bg-gray-300 my-1"></div>
                <div class="px-4 py-2 border border-dashed border-gray-400 rounded-lg text-gray-500 text-sm italic">
                    Belum ada penawaran dibuat...
                </div>
            </div>
        @endif

    </div>
</div>