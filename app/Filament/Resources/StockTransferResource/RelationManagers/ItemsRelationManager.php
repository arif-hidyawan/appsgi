<?php

namespace App\Filament\Resources\StockTransferResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\ProductStock;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Daftar Barang yang Dipindah';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- 1. PILIH PRODUK ---
                Forms\Components\Select::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan(2)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if (!$state) {
                            $set('available_stock', 0);
                            return;
                        }

                        $sourceCompanyId = $this->getOwnerRecord()->source_company_id;
                        $sourceWarehouseId = $this->getOwnerRecord()->source_warehouse_id;

                        $stock = ProductStock::where('product_id', $state)
                            ->where('company_id', $sourceCompanyId)
                            ->where('warehouse_id', $sourceWarehouseId)
                            ->first();

                        $set('available_stock', $stock?->quantity ?? 0);
                    }),

                // --- 2. INFO STOK TERSEDIA ---
                Forms\Components\TextInput::make('available_stock')
                    ->label('Stok Tersedia (Asal)')
                    ->numeric()
                    ->readOnly()
                    ->dehydrated(false)
                    ->prefix('Qty:')
                    ->helperText('Jumlah stok di perusahaan/gudang asal saat ini.')
                    ->columnSpan(1),
                
                // --- 3. INPUT QTY MUTASI ---
                Forms\Components\TextInput::make('qty')
                    ->label('Qty Mutasi')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->rules([
                        fn (Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                            if ($value > $get('available_stock')) {
                                $fail("Jumlah mutasi tidak boleh melebihi stok tersedia ({$get('available_stock')}).");
                            }
                        },
                    ])
                    ->columnSpan(1),
            ])->columns(4);
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
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.item_code')
                    ->label('Kode Barang')
                    ->copyable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty Mutasi')
                    ->alignCenter()
                    ->weight('bold')
                    ->badge()
                    ->color('warning'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Barang')
                    ->modalHeading('Input Barang Mutasi')
                    ->modalSubmitActionLabel('Tambah') // Ganti "Buat" jadi "Tambah"
                    ->createAnother(false) // Hilangkan tombol "Buat & buat lainnya"
                    ->icon('heroicon-m-plus')
                    ->hidden(fn () => $this->getOwnerRecord()->status === 'Completed'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalSubmitActionLabel('Simpan') // Untuk edit biasanya "Simpan" lebih cocok daripada "Tambah"
                    ->hidden(fn () => $this->getOwnerRecord()->status === 'Completed'),
                
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn () => $this->getOwnerRecord()->status === 'Completed'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn () => $this->getOwnerRecord()->status === 'Completed'),
                ]),
            ]);
    }
}