<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Actions\Action; 
use Filament\Notifications\Notification;
use App\Models\SystemNotification; // Tetap pakai Model Manual agar stabil
use Illuminate\Support\Str;

class Pesan extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';
    protected static ?string $navigationLabel = 'Kotak Pesan';
    protected static ?string $title = 'Kotak Pesan Masuk';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 93;

    protected static string $view = 'filament.pages.pesan';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Mengambil semua notifikasi milik User yang sedang Login
                // Menggunakan model SystemNotification untuk membaca tabel 'notifications' secara langsung
                SystemNotification::query()
                    ->where('notifiable_id', auth()->id())
                    ->orderBy('created_at', 'desc')
            )
            ->poll('10s') // Auto refresh setiap 10 detik
            ->columns([
                Tables\Columns\TextColumn::make('data.title')
                    ->label('Judul')
                    ->weight('bold')
                    ->searchable()
                    ->default('Tanpa Judul'),
                
                Tables\Columns\TextColumn::make('data.body')
                    ->label('Pesan')
                    ->limit(255) // UPDATE: Diperpanjang agar pesan panjang terbaca
                    ->wrap()
                    ->default('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->since()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('read_at')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope-open')
                    ->falseIcon('heroicon-o-envelope')
                    ->trueColor('gray')
                    ->falseColor('primary')
                    ->getStateUsing(fn ($record) => $record->read_at !== null),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                // Action Baca
                Tables\Actions\Action::make('mark_as_read')
                    ->label('Baca')
                    ->icon('heroicon-o-check')
                    ->action(fn (SystemNotification $record) => $record->update(['read_at' => now()]))
                    ->visible(fn (SystemNotification $record) => $record->read_at === null),
                
                // Action Lihat (Jika ada link url di dalam data notifikasi)
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn (SystemNotification $record) => $record->data['actions'][0]['url'] ?? null)
                    ->openUrlInNewTab()
                    ->visible(fn (SystemNotification $record) => isset($record->data['actions'][0]['url'])),
                
                // Action Hapus
                Tables\Actions\Action::make('delete')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->action(fn (SystemNotification $record) => $record->delete()),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_read_all')
                    ->label('Tandai Semua Dibaca')
                    ->icon('heroicon-o-check-badge')
                    ->action(fn ($records) => $records->each->update(['read_at' => now()]))
                    ->deselectRecordsAfterCompletion(),
                
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}