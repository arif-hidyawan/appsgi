<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\SalesOrderResource;

class SalesHistoryRelationManager extends RelationManager
{
    // Nama method relasi di Model Product (Kita buat di langkah 2)
    protected static string $relationship = 'salesOrderItems';

    protected static ?string $title = 'Riwayat Harga Jual';

    protected static ?string $icon = 'heroicon-o-banknotes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('unit_price')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_id')
            ->columns([
                // Tanggal SO
                Tables\Columns\TextColumn::make('salesOrder.date')
                    ->label('Tanggal Jual')
                    ->date('d M Y')
                    ->sortable(),

                // Customer
                Tables\Columns\TextColumn::make('salesOrder.customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                // No. SO (Link ke detail SO)
                Tables\Columns\TextColumn::make('salesOrder.so_number')
                    ->label('No. SO')
                    ->searchable()
                    ->url(fn ($record) => SalesOrderResource::getUrl('edit', ['record' => $record->sales_order_id]))
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->iconPosition('after'),

                // Qty Terjual
                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty')
                    ->alignCenter(),

                // Harga Jual Satuan
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort(function ($query) {
                // Sorting berdasarkan tanggal SO terbaru
                return $query->select('sales_order_items.*')
                    ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
                    ->orderBy('sales_orders.date', 'desc');
            });
    }
}