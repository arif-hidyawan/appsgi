<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoodsReceiveResource\Pages;
use App\Filament\Resources\GoodsReceiveResource\RelationManagers;
use App\Models\GoodsReceive;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\PurchaseOrderResource;
use App\Filament\Concerns\HasPermissionPrefix;

class GoodsReceiveResource extends Resource
{
    protected static ?string $model = GoodsReceive::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';
    protected static ?string $navigationGroup = 'Inventory'; 
    protected static ?string $modelLabel = 'Penerimaan Barang';
    protected static ?int $navigationSort = 7;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'goods_receive';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Info Penerimaan')
                            ->schema([
                                Forms\Components\TextInput::make('gr_number')
                                    ->label('No. Terima')
                                    ->default('GR-' . now()->format('Ymd') . '-' . rand(100, 999))
                                    ->required()
                                    ->disabledOn('edit')
                                    ->dehydrated()
                                    ->readOnly(),

                                Forms\Components\TextInput::make('vendor_delivery_number')
                                    ->label('No. Surat Jalan Vendor')
                                    ->placeholder('Masukkan nomor surat jalan dari vendor')
                                    ->required(),

                                Forms\Components\DatePicker::make('date')
                                    ->label('Tanggal Terima')
                                    ->default(now())
                                    ->required(),

                                    Forms\Components\Select::make('warehouse_id')
    ->label('Gudang Penerimaan')
    ->relationship('warehouse', 'name') // Pastikan relasi di model GoodsReceive ada
    ->required()
    ->default(fn() => \App\Models\Warehouse::first()->id ?? null), // Default warehouse

                                // --- FLAG PERUSAHAAN (BARU) ---
                                Forms\Components\Select::make('company_id')
                                    ->label('Perusahaan')
                                    ->relationship('company', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled() // Biasanya auto-fill dari PO
                                    ->dehydrated(),

                                Forms\Components\Select::make('purchase_order_id')
                                    ->label('Referensi PO')
                                    ->relationship('purchaseOrder', 'po_number')
                                    ->searchable()
                                    ->disabled() 
                                    ->required(),

                                Forms\Components\Select::make('vendor_id')
                                    ->label('Vendor')
                                    ->relationship('vendor', 'name')
                                    ->disabled()
                                    ->required(),

                                    Forms\Components\Select::make('vendor_contact_id')
                                    ->label('PIC Vendor')
                                    // --- PERBAIKAN DI SINI: Gunakan 'vendorContact' ---
                                    ->relationship('vendorContact', 'pic_name') 
                                    ->disabledOn('edit')
                                    ->placeholder('PIC sesuai PO'),
                            ])->columns(2),

                        // --- SECTION AUDIT TRAIL ---
                        Forms\Components\Section::make('Audit Trail')
                            ->description('Informasi waktu pembuatan dan perubahan data')
                            ->collapsed()
                            ->compact()
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
                                        ->label('Diubah Oleh Terakhir')
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
                Tables\Columns\TextColumn::make('gr_number')
                    ->label('No. GR')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->label('Tanggal')
                    ->sortable(),

                // --- FLAG PERUSAHAAN (TABEL) ---
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->icon('heroicon-m-building-office')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable(),

                    Tables\Columns\TextColumn::make('vendorContact.pic_name') // Ubah 'contact.pic_name' jadi 'vendorContact.pic_name'
                    ->label('PIC Vendor')
                    ->icon('heroicon-m-user-circle')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state, GoodsReceive $record) => 
                        // Ubah $record->contact jadi $record->vendorContact
                        $state . ($record->vendorContact?->phone ? ' - ' . $record->vendorContact->phone : '')
                    ),
                Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                    ->label('Ref PO')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->iconPosition('after')
                    ->color('gray')
                    ->badge()
                    ->url(fn (GoodsReceive $record): ?string => 
                        $record->purchase_order_id 
                            ? PurchaseOrderResource::getUrl('edit', ['record' => $record->purchase_order_id]) 
                            : null
                    )
                    ->openUrlInNewTab()
                    ->placeholder('-'),

                    Tables\Columns\TextColumn::make('items.product.name')
                    ->label('Item Produk')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->badge()
                    ->color('gray')
                    // Logic pencarian khusus relasi HasMany (GoodsReceive -> Items -> Product)
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('items.product', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('vendor_delivery_number')
                    ->label('SJ Vendor')
                    ->icon('heroicon-m-document-text')
                    ->color('gray')
                    ->badge()
                    ->copyable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updater.name')
                    ->label('Diubah Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // 1. Filter Perusahaan
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                // 2. Filter Vendor (BARU)
                Tables\Filters\SelectFilter::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                // --- ACTION GROUP / DROPDOWN MENU ---
                Tables\Actions\ActionGroup::make([
                    
                    // 1. CETAK BUKTI TERIMA
                    Tables\Actions\Action::make('print')
                        ->label('Cetak Bukti Terima')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->url(fn (GoodsReceive $record) => route('print.gr', $record))
                        ->openUrlInNewTab(),

                    // 2. ALUR DOKUMEN
                    Tables\Actions\Action::make('traceability')
                        ->label('Alur Dokumen')
                        ->icon('heroicon-o-arrows-right-left')
                        ->color('info')
                        ->modalHeading('Riwayat Alur Dokumen')
                        ->modalSubmitAction(false) 
                        ->modalCancelActionLabel('Tutup')
                        ->modalContent(function (GoodsReceive $record) {
                            // Menampilkan alur dari PO terkait
                            return view('filament.components.po-traceability', ['record' => $record->purchaseOrder]);
                        }),

                    // 3. EDIT (Standar)
                    Tables\Actions\EditAction::make(),

                    // 4. DELETE (Standar)
                    Tables\Actions\DeleteAction::make(),
                ])
                ->label('Aksi')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('info')
                ->tooltip('Menu Pilihan'),
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
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoodsReceives::route('/'),
            'create' => Pages\CreateGoodsReceive::route('/create'),
            'edit' => Pages\EditGoodsReceive::route('/{record}/edit'),
        ];
    }
}