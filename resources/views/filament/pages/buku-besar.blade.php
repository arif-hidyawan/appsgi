<x-filament-panels::page>
    {{-- Form Filter --}}
    {{ $this->form }}

    {{-- Tabel Laporan --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mt-6 p-6">
        
        {{-- Header Info Akun --}}
        @if($this->selectedAccount)
            <div class="mb-6 border-b pb-4">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                    {{ $this->selectedAccount->code }} - {{ $this->selectedAccount->name }}
                </h2>
                <div class="text-sm text-gray-500 flex gap-4 mt-1">
                    <span>Posisi Normal: <strong class="uppercase">{{ $this->normal_pos }}</strong></span>
                    <span>Periode: {{ \Carbon\Carbon::parse($this->start_date)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($this->end_date)->format('d M Y') }}</span>
                </div>
            </div>
        @endif

        {{-- Tabel Data --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase font-semibold">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">No. Ref</th>
                        <th class="px-4 py-3">Keterangan</th>
                        <th class="px-4 py-3 text-right">Debit</th>
                        <th class="px-4 py-3 text-right">Kredit</th>
                        <th class="px-4 py-3 text-right bg-gray-50 dark:bg-gray-700">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    
                    @forelse($this->ledger_rows as $row)
                        <tr class="transition {{ $row['is_opening'] ? 'bg-yellow-50 dark:bg-yellow-900/10 font-semibold' : 'hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                            
                            {{-- Tanggal --}}
                            <td class="px-4 py-2 whitespace-nowrap">
                                {{ $row['date']->format('d/m/Y') }}
                                @if(!$row['is_opening'])
                                    <span class="text-xs text-gray-400 block">{{ $row['date']->format('H:i') }}</span>
                                @endif
                            </td>

                            {{-- No Ref --}}
                            <td class="px-4 py-2 text-primary-600">
                                {{ $row['ref'] }}
                            </td>

                            {{-- Keterangan --}}
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-300">
                                {{ $row['description'] }}
                            </td>

                            {{-- Debit --}}
                            <td class="px-4 py-2 text-right font-mono {{ $row['debit'] > 0 ? 'text-gray-900 dark:text-gray-100' : 'text-gray-300' }}">
                                {{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '-' }}
                            </td>

                            {{-- Kredit --}}
                            <td class="px-4 py-2 text-right font-mono {{ $row['credit'] > 0 ? 'text-gray-900 dark:text-gray-100' : 'text-gray-300' }}">
                                {{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '-' }}
                            </td>

                            {{-- Saldo Berjalan --}}
                            <td class="px-4 py-2 text-right font-mono font-bold bg-gray-50 dark:bg-gray-700">
                                {{ number_format($row['balance'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-500 italic">
                                Silakan pilih Akun dan Periode Tanggal, lalu klik tombol "Tampilkan".
                            </td>
                        </tr>
                    @endforelse

                </tbody>
                
                {{-- Footer Total --}}
                @if(count($this->ledger_rows) > 0)
                    <tfoot class="bg-gray-100 dark:bg-gray-800 font-bold border-t-2 border-gray-300 dark:border-gray-600">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right uppercase">Total Mutasi & Saldo Akhir</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ number_format($this->total_debit, 2) }}</td>
                            <td class="px-4 py-3 text-right text-red-600">{{ number_format($this->total_credit, 2) }}</td>
                            
                            {{-- Ambil saldo baris terakhir --}}
                            <td class="px-4 py-3 text-right bg-gray-200 dark:bg-gray-600 text-lg">
                                @if(count($this->ledger_rows) > 0)
                                    {{ number_format(end($this->ledger_rows)['balance'], 2) }}
                                @else
                                    0.00
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</x-filament-panels::page>