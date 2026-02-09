<?php

namespace App\Filament\Resources\PurchaseInvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Rincian Pembelian';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->disabled(),
                Forms\Components\TextInput::make('qty')
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('unit_price')
                    ->label('Harga Beli')
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled(),
                Forms\Components\TextInput::make('subtotal')
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')->label('Produk'),
                Tables\Columns\TextColumn::make('qty')->alignCenter(),
                Tables\Columns\TextColumn::make('unit_price')->money('IDR')->label('Harga Beli'),
                Tables\Columns\TextColumn::make('subtotal')->money('IDR')->weight('bold'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}