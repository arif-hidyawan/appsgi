<?php

namespace App\Filament\Resources\GoodsReceiveResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Barang Diterima';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->disabled() // Karena ambil dari PO, jangan ubah produknya
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('qty_ordered')
                    ->label('Qty Pesan (PO)')
                    ->numeric()
                    ->disabled(),

                Forms\Components\TextInput::make('qty_received')
                    ->label('Qty Diterima (Fisik)')
                    ->helperText('Otomatis menambah stok produk saat disimpan.')
                    ->numeric()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                Tables\Columns\ImageColumn::make('product.image')
                    ->label('Foto')
                    ->circular(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk')
                    ->wrap(),

                Tables\Columns\TextColumn::make('qty_ordered')
                    ->label('Pesan')
                    ->alignCenter()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('qty_received')
                    ->label('Terima')
                    ->alignCenter()
                    ->weight('bold')
                    ->color('success'),

                // --- 1. AMBIL HARGA DARI PO TERKAIT ---
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Harga PO')
                    ->money('IDR')
                    ->state(function (Model $record) {
                        // Logic: Cari item di PO yang produknya sama dengan item GR ini
                        $poId = $record->goodsReceive->purchase_order_id;
                        $poItem = \App\Models\PurchaseOrderItem::where('purchase_order_id', $poId)
                            ->where('product_id', $record->product_id)
                            ->first();
                        
                        return $poItem ? $poItem->unit_price : 0;
                    }),

                // --- 2. HITUNG TOTAL NILAI (QTY TERIMA * HARGA) ---
                Tables\Columns\TextColumn::make('total_value')
                    ->label('Total Nilai')
                    ->money('IDR')
                    ->weight('bold')
                    ->state(function (Model $record) {
                        $poId = $record->goodsReceive->purchase_order_id;
                        $poItem = \App\Models\PurchaseOrderItem::where('purchase_order_id', $poId)
                            ->where('product_id', $record->product_id)
                            ->first();
                        
                        $price = $poItem ? $poItem->unit_price : 0;
                        
                        return $record->qty_received * $price;
                    }),
            ])
            ->headerActions([
                // Kita matikan tombol Create manual agar user generate dari PO saja
                // Tables\Actions\CreateAction::make(), 
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Delete kita izinkan untuk koreksi (stok akan berkurang otomatis via Model Event)
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}