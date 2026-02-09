<div class="overflow-x-auto">
    @if($history->isEmpty())
        <div class="text-center p-4 text-gray-500">
            Belum ada riwayat pembelian untuk produk ini.
        </div>
    @else
        <table class="w-full text-sm text-left text-gray-500 border rounded-lg">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-4 py-2 border-b">Tanggal PO</th>
                    <th class="px-4 py-2 border-b">Nama Vendor</th>
                    <th class="px-4 py-2 border-b text-right">Harga Satuan</th>
                    <th class="px-4 py-2 border-b text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($history as $item)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-4 py-2">
                            {{ \Carbon\Carbon::parse($item->date)->format('d M Y') }}
                        </td>
                        <td class="px-4 py-2 font-medium text-gray-900">
                            {{ $item->vendor_name }}
                        </td>
                        <td class="px-4 py-2 text-right font-bold text-gray-700">
                            Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2 text-center">
                            {{-- INI KUNCINYA: kirim parameter sebagai Object JS --}}
                            <x-filament::button
                                size="xs"
                                color="info" 
                                type="button"
                                wire:click="callMountedFormComponentAction({ vendor_id: {{ $item->vendor_id }}, cost_price: {{ $item->unit_price }} })"
                            >
                                Pilih
                            </x-filament::button>
                        </td>
                    </tr>
                @endforeach
            </tbody>

            @if(isset($avgData) && $avgData['price'] > 0)
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <td colspan="2" class="px-4 py-3 font-medium text-gray-800">
                            <div class="flex items-center gap-2">
                                <x-filament::icon
                                    icon="heroicon-m-calculator"
                                    class="h-5 w-5 text-warning-600"
                                />
                                <span>Rata-rata ({{ $avgData['count'] }} PO Terakhir)</span>
                            </div>
                            <div class="text-xs text-gray-500 mt-1 font-normal ml-7">
                                Menggunakan Vendor PO Terakhir
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-gray-800 text-base">
                            Rp {{ number_format($avgData['price'], 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            {{-- BUTTON RATA-RATA JUGA SAMA --}}
                            <x-filament::button
                                size="xs"
                                color="warning"
                                type="button"
                                wire:click="callMountedFormComponentAction({ vendor_id: {{ $avgData['vendor_id'] }}, cost_price: {{ $avgData['price'] }} })"
                            >
                                Pilih
                            </x-filament::button>
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    @endif
</div>