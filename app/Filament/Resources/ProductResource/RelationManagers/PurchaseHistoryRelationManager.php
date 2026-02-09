<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\PurchaseOrderResource;

class PurchaseHistoryRelationManager extends RelationManager
{
    // Ini nama method relasi di model Product.
    // Nanti kita pastikan method ini ada di langkah ke-2.
    protected static string $relationship = 'purchaseOrderItems';

    protected static ?string $title = 'Riwayat Harga Beli';

    protected static ?string $icon = 'heroicon-o-currency-dollar';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('unit_price')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_id') // Tidak terlalu penting karena read-only
            ->columns([
                // Kolom Tanggal (Diambil dari tabel PurchaseOrder via relasi)
                Tables\Columns\TextColumn::make('purchaseOrder.date')
                    ->label('Tanggal Beli')
                    ->date('d M Y')
                    ->sortable(),

                // Kolom Vendor (Diambil dari PurchaseOrder -> Vendor)
                Tables\Columns\TextColumn::make('purchaseOrder.vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                // Kolom No PO
                Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                    ->label('No. PO')
                    ->searchable()
                    ->url(fn ($record) => PurchaseOrderResource::getUrl('edit', ['record' => $record->purchase_order_id]))
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->iconPosition('after'),

                // Kolom Qty Beli
                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty')
                    ->alignCenter(),

                // Kolom Harga Beli Satuan (Unit Price)
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Harga Beli')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
            ])
            ->filters([
                // Filter berdasarkan rentang tanggal jika perlu
            ])
            ->headerActions([
                // Tidak butuh create di sini karena ini data history
            ])
            ->actions([
                // Tidak butuh edit/delete di sini
            ])
            ->bulkActions([
                // Tidak butuh bulk delete
            ])
            ->defaultSort(function ($query) {
                // Sorting default berdasarkan tanggal PO terbaru
                // Kita perlu join tabel purchase_orders untuk sorting ini
                return $query->select('purchase_order_items.*')
                    ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
                    ->orderBy('purchase_orders.date', 'desc');
            });
    }
}