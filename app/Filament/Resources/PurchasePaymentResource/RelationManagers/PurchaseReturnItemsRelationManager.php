<?php

namespace App\Filament\Resources\PurchaseReturnResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseReturnItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Item Retur';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('qty')
                    ->label('Qty Retur')
                    ->numeric()
                    ->default(1)
                    ->required(),

                Forms\Components\Select::make('reason')
                    ->label('Alasan')
                    ->options([
                        'Damaged' => 'Barang Rusak / Cacat',
                        'Wrong Item' => 'Salah Kirim Barang',
                        'Wrong Spec' => 'Spesifikasi Tidak Sesuai',
                        'Expired' => 'Kadaluarsa',
                        'Other' => 'Lainnya',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty')
                    ->alignCenter()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Alasan')
                    ->badge()
                    ->color('danger'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Item'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}