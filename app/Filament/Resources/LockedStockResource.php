<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LockedStockResource\Pages;
use App\Models\SalesOrderItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

use App\Filament\Concerns\HasPermissionPrefix;

class LockedStockResource extends Resource
{
    protected static ?string $model = SalesOrderItem::class; 

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Stok Terkunci';
    protected static ?string $modelLabel = 'Item Terkunci';
    protected static ?string $pluralModelLabel = 'Stok Terkunci';
    protected static ?int $navigationSort = 10;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'product_stock';

    public static function getEloquentQuery(): Builder
    {
        // FILTER UTAMA: Hanya tampilkan item yang punya tanggal reservasi (Terkunci)
        // Dan Sales Order-nya masih AKTIF (belum Cancelled/Completed)
        return parent::getEloquentQuery()
            ->whereNotNull('reserved_at')
            ->whereHas('salesOrder', function ($q) {
                // PERBAIKAN LOGIKA DISINI:
                // Masukkan status 'Siap Kirim' agar tetap muncul di list
                $q->whereNotIn('status', ['Completed', 'Cancelled']); 
            })
            ->with(['product', 'salesOrder.customer', 'warehouse']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('product.image')
                    ->label('Foto')
                    ->circular(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk')
                    ->description(fn ($record) => $record->product->item_code)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty Terkunci')
                    ->badge()
                    ->color('danger') // Merah biar kelihatan ini stok ditahan
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Lokasi Gudang')
                    ->icon('heroicon-m-building-storefront')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('salesOrder.so_number')
                    ->label('Ref Sales Order')
                    ->searchable()
                    ->description(fn ($record) => $record->salesOrder->customer->name ?? '-')
                    ->url(fn ($record) => \App\Filament\Resources\SalesOrderResource::getUrl('edit', ['record' => $record->sales_order_id]))
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->weight('bold'),
                
                // Tambahkan Status SO agar admin gudang tahu mana yang prioritas (Siap Kirim)
                Tables\Columns\TextColumn::make('salesOrder.status')
                    ->label('Status SO')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'New' => 'Baru',
                        'Processed' => 'Diproses',
                        'Siap Kirim' => 'Siap Kirim',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'New' => 'info',
                        'Processed' => 'warning',
                        'Siap Kirim' => 'success', // Hijau mencolok biar diprioritaskan
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('reserved_at')
                    ->label('Dikunci Sejak')
                    ->since() 
                    ->sortable()
                    ->dateTimeTooltip(),
            ])
            ->defaultSort('reserved_at', 'desc') 
            ->filters([
                // Filter berdasarkan Gudang
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Gudang')
                    ->relationship('warehouse', 'name'),
                
                // Filter berdasarkan Produk
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                
                // Filter Status SO (Biar bisa sort mana yang Siap Kirim)
                Tables\Filters\SelectFilter::make('so_status')
                    ->label('Status SO')
                    ->options([
                        'New' => 'Baru',
                        'Processed' => 'Diproses',
                        'Siap Kirim' => 'Siap Kirim',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('salesOrder', function ($q) use ($data) {
                                $q->where('status', $data['value']);
                            });
                        }
                    }),
            ])
            ->actions([
                // ACTION LEPAS KUNCI (Manual Release)
                Tables\Actions\Action::make('unlock')
                    ->label('Lepas Kunci')
                    ->icon('heroicon-o-lock-open')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Lepas Kunci Stok?')
                    ->modalDescription('Stok ini akan dikembalikan menjadi Stok Bebas (Available) dan bisa diambil oleh Sales Order lain. Pastikan koordinasi dengan Sales terkait.')
                    ->action(function (SalesOrderItem $record) {
                        $record->update([
                            'warehouse_id' => null,
                            'reserved_at' => null,
                        ]);
                        
                        Notification::make()
                            ->title('Stok Berhasil Dilepas')
                            ->body("Booking untuk SO {$record->salesOrder->so_number} telah dihapus.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                // BULK ACTION LEPAS KUNCI
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('unlock_bulk')
                        ->label('Lepas Kunci Terpilih')
                        ->icon('heroicon-o-lock-open')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $count = $records->count();
                            $records->each->update([
                                'warehouse_id' => null,
                                'reserved_at' => null,
                            ]);
                            
                            Notification::make()
                                ->title("{$count} Item Berhasil Dilepas Kuncinya")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLockedStocks::route('/'),
        ];
    }
}