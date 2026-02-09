<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesReturnResource\Pages;
use App\Filament\Resources\SalesReturnResource\RelationManagers;
use App\Models\SalesReturn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Concerns\HasPermissionPrefix;

class SalesReturnResource extends Resource
{
    protected static ?string $model = SalesReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?string $modelLabel = 'Retur Penjualan';

    protected static ?int $navigationSort = 4;

    // use HasPermissionPrefix; 
    // protected static ?string $permissionPrefix = 'sales_return';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Retur')
                            ->schema([
                                Forms\Components\TextInput::make('return_number')
                                    ->label('No. Retur')
                                    ->default('SR-' . now()->format('Ymd') . '-' . rand(100, 999))
                                    ->required()
                                    ->readOnly(),

                                Forms\Components\DatePicker::make('date')
                                    ->label('Tanggal Retur')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\Select::make('customer_id')
                                    ->label('Customer')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabledOn('edit'),

                                Forms\Components\Select::make('company_id')
                                    ->label('Perusahaan')
                                    ->relationship('company', 'name')
                                    ->required()
                                    ->default(1),

                                // Relasi ke Dokumen Asal
                                Forms\Components\Select::make('delivery_order_id')
                                    ->label('Ref Surat Jalan (DO)')
                                    ->relationship('deliveryOrder', 'do_number')
                                    ->searchable()
                                    ->preload()
                                    ->disabledOn('edit'),

                                Forms\Components\Select::make('sales_order_id')
                                    ->label('Ref Sales Order (SO)')
                                    ->relationship('salesOrder', 'so_number')
                                    ->searchable()
                                    ->disabledOn('edit'),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'Draft' => 'Draft',
                                        'Approved' => 'Disetujui (Stok Masuk)',
                                        'Rejected' => 'Ditolak',
                                    ])
                                    ->default('Draft')
                                    ->required(),
                            ])->columns(2),

                        // --- REPEATER DIHAPUS, DIGANTI RELATION MANAGER DI BAWAH ---

                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Retur')
                            ->rows(3)
                            ->columnSpanFull(),

                        // AUDIT TRAIL
                        Forms\Components\Section::make('Audit Trail')
                            ->description('Log pembuatan dan perubahan data')
                            ->collapsed()
                            ->schema([
                                Forms\Components\Group::make([
                                    Forms\Components\Placeholder::make('created_by_name')
                                        ->label('Dibuat Oleh')
                                        ->content(fn ($record) => $record?->creator?->name ?? '-'),
                                    Forms\Components\Placeholder::make('created_at')
                                        ->label('Waktu Dibuat')
                                        ->content(fn ($record) => $record?->created_at?->format('d M Y H:i') ?? '-'),
                                ])->columns(2),

                                Forms\Components\Group::make([
                                    Forms\Components\Placeholder::make('updated_by_name')
                                        ->label('Diubah Oleh')
                                        ->content(fn ($record) => $record?->updater?->name ?? '-'),
                                    Forms\Components\Placeholder::make('updated_at')
                                        ->label('Waktu Perubahan')
                                        ->content(fn ($record) => $record?->updated_at?->format('d M Y H:i') ?? '-'),
                                ])->columns(2),
                            ])
                            ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord),
                    ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('return_number')
                    ->label('No. Retur')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('deliveryOrder.do_number')
                    ->label('Ex. Surat Jalan')
                    ->icon('heroicon-m-document-text')
                    ->color('gray')
                    ->badge()
                    ->searchable(),

                    Tables\Columns\TextColumn::make('items.product.name')
                    ->label('Item Produk')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->badge()
                    ->color('gray')
                    // Logic pencarian khusus relasi HasMany (SalesReturn -> Items -> Product)
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('items.product', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Jml Item')
                    ->counts('items')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Draft' => 'Draft',
                        'Approved' => 'Disetujui',
                        'Rejected' => 'Ditolak',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->label('Customer')
                    ->searchable(),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Draft' => 'Draft',
                        'Approved' => 'Disetujui',
                        'Rejected' => 'Ditolak',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('print')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    // ->url(fn (SalesReturn $record) => route('print.sales_return', $record)) 
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // --- DAFTARKAN RELATION MANAGER DISINI ---
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesReturns::route('/'),
            'create' => Pages\CreateSalesReturn::route('/create'),
            'edit' => Pages\EditSalesReturn::route('/{record}/edit'),
        ];
    }
}