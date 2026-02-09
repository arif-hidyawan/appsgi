<?php

namespace App\Filament\Resources\DeliveryOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Barang Dikirim';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->disabled()
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('qty_ordered')
                    ->label('Qty Pesan (SO)')
                    ->numeric()
                    ->disabled(),

                Forms\Components\TextInput::make('qty_delivered')
                    ->label('Qty Dikirim')
                    ->helperText('Stok gudang akan berkurang sejumlah ini.')
                    ->numeric()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('product.image')->label('Foto')->circular(),
                Tables\Columns\TextColumn::make('product.name')->label('Produk'),
                Tables\Columns\TextColumn::make('qty_ordered')->label('Order SO')->alignCenter(),
                Tables\Columns\TextColumn::make('qty_delivered')->label('Kirim')->alignCenter()->weight('bold')->color('danger'),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}