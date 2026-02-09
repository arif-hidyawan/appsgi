<div class="overflow-x-auto">
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-4 py-3">Tanggal Kunjungan</th>
                <th scope="col" class="px-4 py-3">Salesman</th>
                <th scope="col" class="px-4 py-3">Status</th>
                <th scope="col" class="px-4 py-3">Foto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($histories as $history)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ $history->last_visit_date->translatedFormat('d F Y') }}
                    </td>
                    <td class="px-4 py-3">
                        {{ $history->user->name ?? '-' }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded text-xs font-semibold
                            @if($history->status == 'Hot') bg-red-100 text-red-800 
                            @elseif($history->status == 'Cold') bg-blue-100 text-blue-800
                            @elseif($history->status == 'Quotation') bg-yellow-100 text-yellow-800
                            @elseif($history->status == 'RFQ') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ $history->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($history->photo)
                            <a href="{{ asset('storage/' . $history->photo) }}" target="_blank" class="text-blue-600 hover:underline">
                                Lihat Foto
                            </a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-3 text-center">Belum ada riwayat kunjungan lain.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>