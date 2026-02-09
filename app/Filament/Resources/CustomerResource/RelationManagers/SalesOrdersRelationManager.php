<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\SalesOrderResource; // Import Resource Sales Order

class SalesOrdersRelationManager extends RelationManager
{
    // Nama relasi harus sama dengan nama function di Model Customer
    protected static string $relationship = 'salesOrders';

    // Judul Tab
    protected static ?string $title = 'Riwayat Pembelian (SO)';

    // Icon Tab
    protected static ?string $icon = 'heroicon-o-shopping-bag';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('so_number')
                    ->required()
                    ->maxLength(255),
                // Biasanya di Relation Manager kita hanya menampilkan data (Read Only)
                // Jadi form ini jarang dipakai kecuali Anda ingin create SO langsung dari sini.
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('so_number')
            ->columns([
                Tables\Columns\TextColumn::make('so_number')
                    ->label('No. SO')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_po_number')
                    ->label('PO Customer')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('IDR')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'New' => 'info',
                        'Processed' => 'warning',
                        'Completed' => 'success',
                        'Cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                // Filter status jika perlu
            ])
            ->headerActions([
                // Opsional: Tombol buat SO baru khusus customer ini
                // Tables\Actions\CreateAction::make(), 
            ])
            ->actions([
                // Tombol "Lihat Detail" yang mengarah ke SalesOrderResource
                Tables\Actions\Action::make('view_detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => SalesOrderResource::getUrl('edit', ['record' => $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->defaultSort('date', 'desc'); // Urutkan dari pembelian terbaru
    }
}