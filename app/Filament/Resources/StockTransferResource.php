<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockTransferResource\Pages;
use App\Filament\Resources\StockTransferResource\RelationManagers;
use App\Models\StockTransfer;
use App\Models\ProductStock;
use App\Models\SalesOrderItem; // Pastikan Import Model Ini
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use App\Filament\Concerns\HasPermissionPrefix;

class StockTransferResource extends Resource
{
    protected static ?string $model = StockTransfer::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $modelLabel = 'Mutasi Stok';
    protected static ?int $navigationSort = 9;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'stock_transfer';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Utama Mutasi')
                            ->schema([
                                // NO TRANSFER
                                Forms\Components\TextInput::make('transfer_number')
                                    ->label('No. Transfer')
                                    ->required()
                                    ->readOnly()
                                    ->afterStateHydrated(function (Forms\Components\TextInput $component, $state) {
                                        if (blank($state)) {
                                            $component->state('TRF-' . now()->format('Ymd') . '-' . rand(100, 999));
                                        }
                                    })
                                    ->dehydrated(true),
                                
                                Forms\Components\DatePicker::make('date')
                                    ->label('Tanggal Mutasi')
                                    ->default(now())
                                    ->required(),

                                // --- SOURCE & DESTINATION ---
                                Forms\Components\Select::make('source_company_id')
                                    ->label('Dari Perusahaan')
                                    ->relationship('sourceCompany', 'name')
                                    ->required()
                                    ->live(),
                                
                                Forms\Components\Select::make('source_warehouse_id')
                                    ->label('Dari Gudang')
                                    ->relationship('sourceWarehouse', 'name')
                                    ->required(),

                                Forms\Components\Select::make('destination_company_id')
                                    ->label('Ke Perusahaan')
                                    ->relationship('destinationCompany', 'name')
                                    ->required()
                                    ->live(),

                                Forms\Components\Select::make('destination_warehouse_id')
                                    ->label('Ke Gudang')
                                    ->relationship('destinationWarehouse', 'name')
                                    ->required(),
                                
                                // STATUS
                                Forms\Components\Select::make('status')
                                    ->label('Status Mutasi')
                                    ->options([
                                        'Draft' => 'Draft',
                                        'Completed' => 'Selesai (Stok Berpindah)',
                                    ])
                                    ->required()
                                    ->afterStateHydrated(fn ($component, $state) => $component->state($state ?? 'Draft'))
                                    ->selectablePlaceholder(false)
                                    ->dehydrated(true),
                            ])
                            ->columns(2)
                            // --- LOGIK KUNCI FORM DI SINI ---
                            ->disabled(fn (?StockTransfer $record) => $record?->status === 'Completed'),
                    ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transfer_number')->label('No. Transfer')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('date')->label('Tanggal')->date()->sortable(),
                Tables\Columns\TextColumn::make('sourceCompany.name')->label('Dari PT')->icon('heroicon-m-arrow-left-start-on-rectangle'),
                Tables\Columns\TextColumn::make('destinationCompany.name')->label('Ke PT')->icon('heroicon-m-arrow-right-end-on-rectangle'),
                Tables\Columns\TextColumn::make('status')
                ->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'Draft' => 'Draft',
                    'Completed' => 'Selesai',
                    default => $state,
                })
                ->color(fn (string $state): string => match ($state) {
                    'Draft' => 'gray',
                    'Completed' => 'success',
                    default => 'primary',
                }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                // PROSES TRANSFER
                Tables\Actions\Action::make('process_transfer')
                    ->label('Proses Pindah Stok')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Mutasi')
                    ->modalDescription('Stok akan dipindahkan. Pastikan stok sumber mencukupi dan tidak sedang dikunci (Reserved).')
                    // Hanya muncul jika Draft dan sudah ada item barangnya
                    ->visible(fn (StockTransfer $record) => $record->status === 'Draft' && $record->items()->count() > 0)
                    ->action(function (StockTransfer $record) {
                        
                        DB::transaction(function () use ($record) {
                            foreach ($record->items as $item) {
                                
                                // 1. Ambil Stok Fisik Asal
                                $sourceStock = ProductStock::where('product_id', $item->product_id)
                                    ->where('company_id', $record->source_company_id)
                                    ->where('warehouse_id', $record->source_warehouse_id)
                                    ->lockForUpdate() // Cegah race condition
                                    ->first();

                                $physicalQty = $sourceStock ? $sourceStock->quantity : 0;

                                // 2. Hitung Stok Reserved (Yang sedang dikunci Sales Order Aktif)
                                $reservedQty = SalesOrderItem::where('product_id', $item->product_id)
                                    ->where('warehouse_id', $record->source_warehouse_id)
                                    ->whereNotNull('reserved_at')
                                    ->whereHas('salesOrder', function($q) use ($record) {
                                        // Filter hanya SO milik perusahaan asal yang belum selesai
                                        $q->where('company_id', $record->source_company_id)
                                          ->whereNotIn('status', ['Completed', 'Cancelled']);
                                    })
                                    ->sum('qty');

                                // 3. Hitung Stok Bebas (Available)
                                $availableQty = $physicalQty - $reservedQty;

                                // 4. Validasi: Apakah Stok Bebas Cukup?
                                if ($availableQty < $item->qty) {
                                    Notification::make()
                                        ->title('Gagal Mutasi')
                                        ->body("Stok Bebas {$item->product->name} tidak cukup.\nFisik: {$physicalQty}, Reserved: {$reservedQty}, Bebas: {$availableQty}. Butuh: {$item->qty}.")
                                        ->danger()
                                        ->send();
                                    
                                    throw new \Exception("Stok kurang (Reserved)");
                                }

                                // 5. Eksekusi Pengurangan Stok Asal
                                $sourceStock->decrement('quantity', $item->qty);

                                // 6. Tambah Stok Tujuan
                                $destStock = ProductStock::firstOrCreate([
                                    'product_id' => $item->product_id,
                                    'company_id' => $record->destination_company_id,
                                    'warehouse_id' => $record->destination_warehouse_id,
                                ], ['quantity' => 0]);
                                
                                $destStock->increment('quantity', $item->qty);
                            }

                            // Update status mutasi
                            $record->update(['status' => 'Completed']);
                        });

                        Notification::make()->title('Mutasi Selesai')->success()->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->label(fn ($record) => $record->status === 'Completed' ? 'Lihat' : 'Ubah')
                    ->icon(fn ($record) => $record->status === 'Completed' ? 'heroicon-m-eye' : 'heroicon-m-pencil-square'),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->status === 'Draft'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockTransfers::route('/'),
            'create' => Pages\CreateStockTransfer::route('/create'),
            'edit' => Pages\EditStockTransfer::route('/{record}/edit'),
        ];
    }
}