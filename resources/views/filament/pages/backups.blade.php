<x-filament-panels::page>
    <div class="flex flex-col gap-4">
        
        <div class="flex justify-between items-center gap-4">
            <div class="flex-1 max-w-sm">
                <x-filament::input.wrapper>
                    <x-filament::input type="search" wire:model.live="search" placeholder="Cari file backup..." />
                </x-filament::input.wrapper>
            </div>
            {{ $this->createBackupAction() }}
        </div>

        <div class="fi-ta-ctn overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <table class="fi-ta-table w-full text-start divide-y divide-gray-200 dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-6 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Nama File</th>
                        <th class="px-6 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Tanggal</th>
                        <th class="px-6 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Ukuran</th>
                        <th class="px-6 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse ($backups as $backup)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-6 py-4 text-sm font-medium text-gray-950 dark:text-white">
                                {{ $backup['name'] }}
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $backup['date']->format('d M Y, H:i:s') }}
                            </td>

                            <td class="px-6 py-4 text-sm">
                                <x-filament::badge color="info">
                                    {{ number_format($backup['size'] / 1024 / 1024, 2) }} MB
                                </x-filament::badge>
                            </td>

                            <td class="px-6 py-4 text-end">
                                <div class="flex justify-end gap-2">
                                    <x-filament::button 
                                        size="sm" 
                                        color="primary" 
                                        icon="heroicon-o-arrow-down-tray" 
                                        wire:click="download('{{ $backup['path'] }}')"
                                    >
                                        DOWNLOAD
                                    </x-filament::button>

                                    <x-filament::icon-button 
                                        color="danger" 
                                        icon="heroicon-o-trash" 
                                        wire:click="delete('{{ $backup['path'] }}')"
                                        wire:confirm="Hapus backup ini?"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                Tidak ada file backup ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>