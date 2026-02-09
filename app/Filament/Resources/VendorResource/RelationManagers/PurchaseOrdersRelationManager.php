<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\PurchaseOrderResource; // Import Resource PO

class PurchaseOrdersRelationManager extends RelationManager
{
    // Nama relasi harus sama dengan di Model Vendor
    protected static string $relationship = 'purchaseOrders';

    protected static ?string $title = 'Riwayat Pembelian (PO)';

    protected static ?string $icon = 'heroicon-o-truck';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('po_number')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('po_number')
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('No. PO')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('IDR')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'Ordered' => 'warning',
                        'Partial' => 'info',
                        'Received' => 'success',
                        'Cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tombol "Lihat Detail" mengarah ke PurchaseOrderResource
                Tables\Actions\Action::make('view_detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => PurchaseOrderResource::getUrl('edit', ['record' => $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->defaultSort('date', 'desc');
    }
}