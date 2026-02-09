<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'Stocks';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('warehouse_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
{
    return $table
        ->recordTitleAttribute('id')
        ->columns([
            Tables\Columns\TextColumn::make('warehouse.name')
                ->label('Gudang'),
            
            // Tampilkan Perusahaan Pemilik Stok
            Tables\Columns\TextColumn::make('company.name')
                ->label('Milik Perusahaan')
                ->icon('heroicon-m-building-office')
                ->sortable(),

            Tables\Columns\TextColumn::make('quantity')
                ->label('Jumlah Stok')
                ->weight('bold'),
                
            Tables\Columns\TextColumn::make('updated_at')
                ->label('Terakhir Update')
                ->dateTime(),
        ])
        ->filters([
            // Filter Stok Perusahaan Tertentu
            Tables\Filters\SelectFilter::make('company_id')
                ->label('Perusahaan')
                ->relationship('company', 'name'),
        ]);
}
}
