<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryOrderResource\Pages;
use App\Filament\Resources\DeliveryOrderResource\RelationManagers;
use App\Models\DeliveryOrder;
use App\Models\ProductStock;
use App\Models\SalesReturn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\SalesOrderResource;
use App\Filament\Resources\SalesReturnResource; // Pastikan Import ini ada
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use App\Filament\Concerns\HasPermissionPrefix;

class DeliveryOrderResource extends Resource
{
    protected static ?string $model = DeliveryOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $modelLabel = 'Delivery Order';
    protected static ?int $navigationSort = 8;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'delivery_order';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Info Pengiriman')
                            ->schema([
                                Forms\Components\TextInput::make('do_number')
                                    ->label('No. Surat Jalan')
                                    ->default('DO-' . now()->format('Ymd') . '-' . rand(100, 999))
                                    ->required()
                                    ->readOnly(),

                                Forms\Components\DatePicker::make('date')
                                    ->label('Tanggal Kirim')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\Select::make('customer_id')
                                    ->label('Customer Tujuan')
                                    ->relationship('customer', 'name')
                                    ->disabled()
                                    ->required(),

                                Forms\Components\Select::make('company_id')
                                    ->label('Perusahaan')
                                    ->relationship('company', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\Select::make('warehouse_id')
                                    ->label('Gudang Pengirim')
                                    ->options(\App\Models\Warehouse::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->dehydrated(),

                                Forms\Components\Select::make('sales_order_id')
                                    ->label('Referensi SO')
                                    ->relationship('salesOrder', 'so_number')
                                    ->disabled()
                                    ->required(),
                                
                                Forms\Components\TextInput::make('vehicle_number')
                                    ->label('Plat Nomor')
                                    ->placeholder('B 1234 XYZ'),
                                
                                Forms\Components\TextInput::make('driver_name')
                                    ->label('Nama Supir')
                                    ->placeholder('Nama Driver'),
                                
                                // --- UPDATE: STATUS TAMBAH 'RETURNED' ---
                                Forms\Components\Select::make('status')
                                    ->label('Status Pengiriman')
                                    ->options([
                                        'Draft' => 'Draft',
                                        'Sent' => 'Dikirim (OTW)',
                                        'Delivered' => 'Sampai Tujuan',
                                        'Returned' => 'Dikembalikan (Retur)', // Tambahan
                                    ])
                                    ->default('Draft')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(),
                            ])->columns(2),

                        Forms\Components\Section::make('Bukti Pengiriman (BAST)')
                            ->schema([
                                Forms\Components\TextInput::make('bast_number')
                                    ->label('Nomor BAST')
                                    ->readOnly()
                                    ->disabled(),

                                Forms\Components\FileUpload::make('attachment')
                                    ->label('Foto Bukti / BAST')
                                    ->image()
                                    ->openable()
                                    ->downloadable()
                                    ->columnSpanFull()
                                    ->disabled(),
                            ])
                            ->visible(fn ($record) => $record && in_array($record->status, ['Delivered', 'Returned']))
                            ->collapsed(),

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
                Tables\Columns\TextColumn::make('do_number')
                    ->label('No. DO')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->label('Tanggal')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->icon('heroicon-m-building-office')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Dari Gudang')
                    ->icon('heroicon-m-home-modern')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('salesOrder.so_number')
                    ->label('Ref SO')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->iconPosition('after')
                    ->color('info')
                    ->badge()
                    ->url(fn (DeliveryOrder $record): ?string => 
                        $record->sales_order_id 
                            ? SalesOrderResource::getUrl('edit', ['record' => $record->sales_order_id]) 
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
                    // Logic pencarian khusus relasi HasMany (DeliveryOrder -> Items -> Product)
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('items.product', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->toggleable(),

                // --- UPDATE: STATUS BADGE TAMBAH 'RETURNED' ---
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Draft' => 'Draft',
                        'Sent' => 'Dikirim (OTW)',
                        'Delivered' => 'Sampai Tujuan',
                        'Returned' => 'Dikembalikan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'Sent' => 'warning',
                        'Delivered' => 'success',
                        'Returned' => 'danger', // Warna Merah untuk Retur
                        default => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('attachment')
                    ->label('Bukti')
                    ->boolean()
                    ->trueIcon('heroicon-o-photo')
                    ->falseIcon('')
                    ->color('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                // --- UPDATE: STATUS FILTER TAMBAH 'RETURNED' ---
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Pengiriman')
                    ->options([
                        'Draft' => 'Draft',
                        'Sent' => 'Dikirim (OTW)',
                        'Delivered' => 'Sampai Tujuan',
                        'Returned' => 'Dikembalikan',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    
                    // 1. ACTION: KIRIM BARANG
                    Tables\Actions\Action::make('send_goods')
                        ->label('Kirim Barang (Potong Stok)')
                        ->icon('heroicon-o-truck')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Pengiriman')
                        ->modalDescription('Stok akan dipotong dari gudang yang sudah direservasi. Sales Order akan ditandai Selesai.')
                        ->modalSubmitActionLabel('Ya, Kirim')
                        ->action(function (DeliveryOrder $record) {
                            DB::transaction(function () use ($record) {
                                foreach ($record->items as $doItem) {
                                    $soItem = \App\Models\SalesOrderItem::where('sales_order_id', $record->sales_order_id)
                                        ->where('product_id', $doItem->product_id)
                                        ->first();

                                    $targetWarehouseId = $soItem?->warehouse_id ?? $record->warehouse_id;

                                    if (!$targetWarehouseId) {
                                        throw new \Exception("Gudang tidak ditemukan untuk produk {$doItem->product->name}.");
                                    }

                                    $stock = ProductStock::where('product_id', $doItem->product_id)
                                        ->where('warehouse_id', $targetWarehouseId)
                                        ->where('company_id', $record->company_id)
                                        ->lockForUpdate()
                                        ->first();

                                    if (!$stock || $stock->quantity < $doItem->qty_delivered) {
                                        Notification::make()
                                            ->title('Gagal Kirim')
                                            ->body("Stok fisik {$doItem->product->name} tidak cukup.")
                                            ->danger()
                                            ->send();
                                        throw new \Exception("Stok kurang");
                                    }

                                    $stock->decrement('quantity', $doItem->qty_delivered);
                                }

                                $record->update(['status' => 'Sent']);

                                if ($record->salesOrder) {
                                    $record->salesOrder->update(['status' => 'Completed']);
                                }
                            });

                            Notification::make()->title('Barang Dikirim & Stok Terpotong')->success()->send();
                        })
                        ->visible(fn (DeliveryOrder $record) => $record->status === 'Draft'),
                    

                    // 2. ACTION: SAMPAI TUJUAN (BAST)
                    Tables\Actions\Action::make('mark_delivered')
                        ->label('Sampai Tujuan')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn (DeliveryOrder $record) => $record->status === 'Sent')
                        ->modalHeading('Konfirmasi Barang Sampai')
                        ->modalDescription('Silakan upload bukti BAST yang sudah ditandatangani.')
                        ->form([
                            Forms\Components\TextInput::make('bast_number')
                                ->label('Nomor BAST / Penerima')
                                ->required(),
                            Forms\Components\FileUpload::make('attachment')
                                ->label('Bukti Foto / Scan BAST')
                                ->image()
                                ->directory('delivery-proofs')
                                ->required()
                                ->maxSize(5120),
                        ])
                        ->action(function (DeliveryOrder $record, array $data) {
                            $record->update([
                                'status' => 'Delivered',
                                'bast_number' => $data['bast_number'],
                                'attachment' => $data['attachment'],
                            ]);

                            Notification::make()->title('Pengiriman Selesai')->success()->send();
                        }),

                    // 3. ACTION BARU: PROSES SALES RETURN + REDIRECT + UPDATE STATUS
                    Tables\Actions\Action::make('process_return')
                        ->label('Proses Retur (Sales Return)')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('danger')
                        ->modalHeading('Retur Barang dari Customer')
                        ->modalDescription('Input jumlah barang yang dikembalikan. Stok akan bertambah di gudang yang dipilih. Status DO akan berubah menjadi "Dikembalikan".')
                        ->form(function (DeliveryOrder $record) {
                            $items = $record->items;
                            $schema = [];

                            foreach ($items as $item) {
                                $schema[] = Forms\Components\Section::make($item->product->name)
                                    ->schema([
                                        Forms\Components\Hidden::make("items.{$item->product_id}.product_id")
                                            ->default($item->product_id),
                                        
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make("items.{$item->product_id}.qty_returned")
                                                ->label('Qty Retur')
                                                ->numeric()
                                                ->default(0)
                                                ->minValue(0)
                                                ->maxValue($item->qty_delivered)
                                                ->helperText("Dikirim: {$item->qty_delivered}"),

                                            Forms\Components\Select::make("items.{$item->product_id}.condition")
                                                ->label('Kondisi Barang')
                                                ->options([
                                                    'Good' => 'Bagus (Masuk Stok Utama)',
                                                    'Bad' => 'Rusak (Masuk Karantina)',
                                                ])
                                                ->default('Good')
                                                ->required(),
                                        ]),
                                    ])->compact();
                            }

                            $schema[] = Forms\Components\Textarea::make('return_reason')
                                ->label('Alasan Retur')
                                ->required();
                            
                            $schema[] = Forms\Components\Select::make('warehouse_id')
                                ->label('Gudang Penerima Retur')
                                ->options(\App\Models\Warehouse::pluck('name', 'id'))
                                ->required()
                                ->default(1)
                                ->helperText('Pilih gudang tujuan pengembalian stok.');

                            return $schema;
                        })
                        ->action(function (DeliveryOrder $record, array $data) {
                            $salesReturn = null; // Variabel penampung untuk redirect

                            DB::transaction(function () use ($record, $data, &$salesReturn) {
                                $hasReturn = false;

                                // 1. Buat Header Sales Return
                                $salesReturn = SalesReturn::create([
                                    'return_number' => 'SR-' . now()->format('ymd') . '-' . rand(1000, 9999),
                                    'date' => now(),
                                    'delivery_order_id' => $record->id,
                                    'sales_order_id' => $record->sales_order_id,
                                    'customer_id' => $record->customer_id,
                                    'company_id' => $record->company_id,
                                    'reason' => $data['return_reason'],
                                    'status' => 'Approved',
                                    'warehouse_id' => $data['warehouse_id'], 
                                    'created_by' => auth()->id(),
                                ]);

                                foreach ($data['items'] as $productId => $itemData) {
                                    $qty = (int) $itemData['qty_returned'];
                                    if ($qty > 0) {
                                        $hasReturn = true;

                                        // 2. Simpan Item Retur
                                        $salesReturn->items()->create([
                                            'product_id' => $productId,
                                            'qty' => $qty,
                                            'condition' => $itemData['condition'],
                                        ]);

                                        // 3. Update Stok (Kembalikan ke Gudang)
                                        $stock = ProductStock::firstOrCreate(
                                            [
                                                'product_id' => $productId,
                                                'warehouse_id' => $data['warehouse_id'],
                                                'company_id' => $record->company_id,
                                            ],
                                            ['quantity' => 0]
                                        );
                                        $stock->increment('quantity', $qty);
                                    }
                                }

                                if (!$hasReturn) {
                                    throw new \Exception("Tidak ada barang yang diretur. Isi Qty Retur minimal satu barang.");
                                }

                                // 4. UPDATE STATUS DELIVERY ORDER -> RETURNED
                                $record->update(['status' => 'Returned']);
                            });

                            Notification::make()
                                ->title('Retur Berhasil Diproses')
                                ->body('Dokumen Sales Return dibuat, Stok dikembalikan, dan Status DO diperbarui.')
                                ->success()
                                ->send()
                                ->sendToDatabase(auth()->user());

                            // 5. REDIRECT KE HALAMAN SALES RETURN
                            return redirect()->to(SalesReturnResource::getUrl('edit', ['record' => $salesReturn->id]));
                        })
                        ->visible(fn (DeliveryOrder $record) => in_array($record->status, ['Delivered', 'Returned'])),

                    // 4. PRINT ACTIONS
                    Tables\Actions\Action::make('print_do')
                        ->label('Cetak Surat Jalan')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->url(fn (DeliveryOrder $record) => route('print.do', $record))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('print_bast')
                        ->label('Cetak BAST')
                        ->icon('heroicon-o-document-text')
                        ->color('gray')
                        ->url(fn (DeliveryOrder $record) => route('print.bast', $record))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('traceability')
                        ->label('Alur Dokumen')
                        ->icon('heroicon-o-arrows-right-left')
                        ->color('info')
                        ->modalSubmitAction(false) 
                        ->modalCancelActionLabel('Tutup')
                        ->modalContent(fn (DeliveryOrder $record) => view('filament.components.so-traceability', ['record' => $record->salesOrder])),

                    Tables\Actions\EditAction::make()
                        ->hidden(fn (DeliveryOrder $record) => in_array($record->status, ['Sent', 'Delivered', 'Returned'])),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (DeliveryOrder $record) => $record->status === 'Draft'),

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
            'index' => Pages\ListDeliveryOrders::route('/'),
            'create' => Pages\CreateDeliveryOrder::route('/create'),
            'edit' => Pages\EditDeliveryOrder::route('/{record}/edit'),
        ];
    }
}