<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseReturnResource\Pages;
use App\Filament\Resources\PurchaseReturnResource\RelationManagers; // <-- Pastikan ini ada
use App\Models\PurchaseReturn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseReturnResource extends Resource
{
    protected static ?string $model = PurchaseReturn::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';
    protected static ?string $navigationGroup = 'Procurement';
    protected static ?string $modelLabel = 'Retur Pembelian';
    protected static ?int $navigationSort = 6;

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
                                    ->default('RET-' . now()->format('Ymd') . '-' . rand(100, 999))
                                    ->readOnly(),
                                
                                Forms\Components\DatePicker::make('date')
                                    ->label('Tanggal')
                                    ->default(now())
                                    ->required(),
                                
                                Forms\Components\Select::make('vendor_id')
                                    ->relationship('vendor', 'name')
                                    ->label('Vendor')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                
                                Forms\Components\Select::make('purchase_order_id')
                                    ->relationship('purchaseOrder', 'po_number')
                                    ->label('Ref PO')
                                    ->searchable(),

                                Forms\Components\Select::make('company_id')
                                    ->relationship('company', 'name')
                                    ->label('Perusahaan')
                                    ->default(1)
                                    ->required(),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'Draft' => 'Draft',
                                        'Sent' => 'Dikirim',
                                        'Completed' => 'Selesai',
                                    ])
                                    ->default('Draft')
                                    ->required(),
                            ])->columns(2),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ])->columnSpanFull()
            ]);
            // HAPUS Bagian Section Repeater 'items' yang lama disini
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('return_number')->label('No. Retur')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('vendor.name')->label('Vendor')->sortable(),
                Tables\Columns\TextColumn::make('purchaseOrder.po_number')->label('Ref PO')->sortable(),
                Tables\Columns\TextColumn::make('items.product.name')
    ->label('Item Produk')
    ->listWithLineBreaks()
    ->limitList(2)
    ->expandableLimitedList()
    ->badge()
    ->color('gray')
    // Logic pencarian khusus relasi HasMany (PurchaseReturn -> Items -> Product)
    ->searchable(query: function ($query, $search) {
        return $query->whereHas('items.product', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        });
    })
    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'Sent' => 'warning',
                        'Completed' => 'success',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            // DAFTARKAN RELATION MANAGER DISINI
            RelationManagers\PurchaseReturnItemsRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseReturns::route('/'),
            'create' => Pages\CreatePurchaseReturn::route('/create'),
            'edit' => Pages\EditPurchaseReturn::route('/{record}/edit'),
        ];
    }
}