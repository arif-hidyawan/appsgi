<x-filament::widget>
    <x-filament::section>
        
        {{-- HEADER & FILTER --}}
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2 style="font-size: 1.1rem; font-weight: 700; color: #1f2937; margin: 0;">Sales Pipeline Overview</h2>
            
            <div style="display: flex; gap: 10px;">
                {{-- Filter Sales --}}
                <div style="width: 180px;">
                    <select wire:model.live="filterSales" style="
                        width: 100%; font-size: 0.875rem; border-radius: 0.5rem; 
                        border: 1px solid #d1d5db; padding: 0.5rem; 
                        color: #374151; background-color: #fff; cursor: pointer; outline: none;">
                        <option value="">Semua Sales</option>
                        @foreach($salesUsers as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Filter Period --}}
                <div style="width: 150px;">
                    <select wire:model.live="filterPeriod" style="
                        width: 100%; font-size: 0.875rem; border-radius: 0.5rem; 
                        border: 1px solid #d1d5db; padding: 0.5rem; 
                        color: #374151; background-color: #fff; cursor: pointer; outline: none;">
                        <option value="today">Hari Ini</option>
                        <option value="this_week">Minggu Ini</option>
                        <option value="this_month">Bulan Ini</option>
                        <option value="this_year">Tahun Ini</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- ROW 1: STATS CARDS --}}
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; width: 100%; margin-bottom: 2rem;">
            {{-- 1. PROSPEK --}}
            <div style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; background-color: #fff;">
                <p style="font-size: 0.875rem; color: #6b7280; font-weight: 600; margin-bottom: 0.5rem; margin-top: 0;">Prospek</p>
                <div style="font-size: 2rem; font-weight: 800; color: #111827; line-height: 1;">{{ number_format($totalProspek) }}</div>
            </div>
            {{-- 2. RFQ --}}
            <div style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; background-color: #fff;">
                <p style="font-size: 0.875rem; color: #6b7280; font-weight: 600; margin-bottom: 0.5rem; margin-top: 0;">RFQ</p>
                <div style="font-size: 2rem; font-weight: 800; color: #111827; line-height: 1;">{{ number_format($totalRfq) }}</div>
                <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px dashed #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.75rem; color: #6b7280;">Berhasil jadi Quotation</span>
                    <span style="font-size: 0.875rem; font-weight: 700; color: #059669;">
                        {{ number_format($rfqConverted) }} 
                        <span style="font-size:0.75rem; color:#9ca3af; font-weight:400">({{ number_format($rfqConversionRate, 1) }}%)</span>
                    </span>
                </div>
            </div>
            {{-- 3. QUOTATION --}}
            <div style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; background-color: #fff;">
                <p style="font-size: 0.875rem; color: #6b7280; font-weight: 600; margin-bottom: 0.5rem; margin-top: 0;">Quotation</p>
                <div style="font-size: 2rem; font-weight: 800; color: #111827; line-height: 1;">{{ number_format($totalQuotation) }}</div>
                <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px dashed #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.75rem; color: #6b7280;">Dikirim ke Customer</span>
                    <span style="font-size: 0.875rem; font-weight: 700; color: #059669;">
                        {{ number_format($quotationSent) }} 
                        <span style="font-size:0.75rem; color:#9ca3af; font-weight:400">({{ number_format($quotationSentRate, 1) }}%)</span>
                    </span>
                </div>
                <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.75rem; color: #6b7280;">Berhasil jadi Sales Order</span>
                    <span style="font-size: 0.875rem; font-weight: 700; color: #2563eb;">
                        {{ number_format($quotationAccepted) }} 
                        <span style="font-size:0.75rem; color:#9ca3af; font-weight:400">({{ number_format($quotationAcceptedRate, 1) }}%)</span>
                    </span>
                </div>
            </div>
            {{-- 4. SALES ORDER --}}
            <div style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; background-color: #fff;">
                <p style="font-size: 0.875rem; color: #6b7280; font-weight: 600; margin-bottom: 0.5rem; margin-top: 0;">Sales Order</p>
                <div style="font-size: 2rem; font-weight: 800; color: #111827; line-height: 1;">{{ number_format($totalSalesOrder) }}</div>
                <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px dashed #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.75rem; color: #6b7280;">Barang sudah dikirim</span>
                    <span style="font-size: 0.875rem; font-weight: 700; color: #059669;">
                        {{ number_format($salesOrderCompleted) }} 
                        <span style="font-size:0.75rem; color:#9ca3af; font-weight:400">({{ number_format($salesOrderCompletedRate, 1) }}%)</span>
                    </span>
                </div>
            </div>
        </div>

        {{-- ROW 2: BAR CHARTS --}}
        <h3 style="font-size: 0.95rem; font-weight: 700; color: #374151; margin-bottom: 1rem; margin-top: 0;">Status Breakdown</h3>
        
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; width: 100%;">
            
            @foreach([
                ['label' => 'Prospek Status', 'data' => $chartProspek, 'color' => '#3b82f6', 'slug' => 'prospeks'],
                ['label' => 'RFQ Status', 'data' => $chartRfq, 'color' => '#f97316', 'slug' => 'rfqs'],
                ['label' => 'Quotation Status', 'data' => $chartQuotation, 'color' => '#a855f7', 'slug' => 'quotations'],
                ['label' => 'SO Status', 'data' => $chartSalesOrder, 'color' => '#22c55e', 'slug' => 'sales-orders']
            ] as $chart)
                
                <div style="
                    border: 1px solid #e5e7eb; 
                    border-radius: 0.75rem; 
                    padding: 1rem; 
                    background-color: #f9fafb;
                    height: 220px; 
                    display: flex;
                    flex-direction: column;
                ">
                    <h4 style="font-size: 0.75rem; font-weight: 700; color: #6b7280; margin: 0 0 1rem 0; text-transform: uppercase; letter-spacing: 0.05em;">
                        {{ $chart['label'] }}
                    </h4>
                    
                    <div style="
                        flex: 1;
                        display: flex; 
                        align-items: flex-end; 
                        justify-content: space-around; 
                        gap: 6px; 
                        padding-bottom: 5px;
                    ">
                        @php 
                            $max = count($chart['data']) > 0 ? max($chart['data']) : 1; 
                        @endphp

                        @forelse($chart['data'] as $status => $count)
                            @php 
                                // Logic Terjemahan
                                $displayStatus = $status;
                                
                                if ($chart['label'] === 'Quotation Status') {
                                    $mapQT = [
                                        'Draft' => 'Draft',
                                        'Sent' => 'Dikirim ke Customer',
                                        'Partial' => 'Diproses PO Sebagian',
                                        'Accepted' => 'Diproses PO Semua',
                                        'Rejected' => 'Ditolak',
                                    ];
                                    $displayStatus = $mapQT[$status] ?? $status;
                                }

                                if ($chart['label'] === 'SO Status') {
                                    $mapSO = [
                                        'New' => 'Baru',
                                        'Processed' => 'Diproses (Stok/PO)',
                                        'Completed' => 'Selesai (Dikirim)',
                                        'Cancelled' => 'Dibatalkan',
                                    ];
                                    $displayStatus = $mapSO[$status] ?? $status;
                                }

                                $percent = ($count / $max) * 100;
                                $height = $percent < 5 ? 5 : $percent; 

                                // LINK FILAMENT: Sesuaikan path admin di sini (misal: /admin/...)
                                // Menggunakan Filter Standard Filament: tableFilters[status][value]=VALUE
                                $link = '/admin/' . $chart['slug'] . '?tableFilters[status][value]=' . urlencode($status);
                            @endphp

                            {{-- WRAPPED WITH A TAG FOR CLICKABLE LINK --}}
                            <a href="{{ $link }}" style="
                                display: flex; 
                                flex-direction: column; 
                                align-items: center; 
                                flex: 1; 
                                height: 100%; 
                                justify-content: flex-end;
                                text-decoration: none; /* Hapus garis bawah link */
                                cursor: pointer;
                            ">
                                {{-- Angka --}}
                                <div style="font-size: 0.7rem; font-weight: bold; color: #374151; margin-bottom: 3px;">
                                    {{ $count }}
                                </div>
                                
                                {{-- Bar --}}
                                <div style="
                                    width: 80%; 
                                    height: {{ $height }}%; 
                                    background-color: {{ $chart['color'] }}; 
                                    border-radius: 4px 4px 0 0; 
                                    opacity: 0.85;
                                    transition: opacity 0.2s ease;
                                " 
                                title="{{ $displayStatus }}: {{ $count }}"
                                onmouseover="this.style.opacity='1'" 
                                onmouseout="this.style.opacity='0.85'"></div>
                                
                                {{-- Label --}}
                                <div style="
                                    font-size: 0.6rem; 
                                    color: #4b5563; 
                                    margin-top: 5px; 
                                    text-align: center; 
                                    line-height: 1.1; 
                                    width: 100%;
                                    white-space: normal; 
                                    word-wrap: break-word;
                                " title="{{ $displayStatus }}">
                                    {{ $displayStatus }}
                                </div>
                            </a>

                        @empty
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 0.8rem; font-style: italic;">
                                No Data
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach

        </div>

    </x-filament::section>
</x-filament::widget>