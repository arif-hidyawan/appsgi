<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Models\PurchaseOrder;
use App\Models\GoodsReceive;
use App\Filament\Resources\GoodsReceiveResource;
use App\Models\PurchaseInvoice;
use App\Filament\Resources\PurchaseInvoiceResource;
use App\Filament\Resources\SalesOrderResource;
use App\Models\ProductStock;
use App\Models\SalesOrder; 
use App\Models\SalesOrderItem;
use App\Models\Warehouse;
use App\Models\VendorContact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Concerns\HasPermissionPrefix;
use Illuminate\Support\Facades\DB;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck'; 
    protected static ?string $navigationGroup = 'Procurement'; 
    protected static ?string $modelLabel = 'Purchase Order';
    protected static ?int $navigationSort = 5;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'purchase_order';
  
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Info Pembelian')
                            ->schema([
                                Forms\Components\TextInput::make('po_number')
                                    ->label('No. PO')
                                    ->default('PO-' . now()->format('Ymd') . '-' . rand(100, 999))
                                    ->required()
                                    ->readOnly()
                                    ->disabledOn('edit')
                                    ->dehydrated(),

                                Forms\Components\DatePicker::make('date')
                                    ->label('Tanggal Order')
                                    ->default(now())
                                    ->required()
                                    ->disabled(fn (?PurchaseOrder $record) => $record && $record->status !== 'Draft'),

                                // --- 1. FLAG PERUSAHAAN ---
                                Forms\Components\Select::make('company_id')
                                    ->label('Perusahaan')
                                    ->relationship('company', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled(fn (?PurchaseOrder $record) => $record && $record->status !== 'Draft'),

                                // --- 2. VENDOR (TRIGGER) ---
                                Forms\Components\Select::make('vendor_id')
                                    ->label('Vendor')
                                    ->relationship('vendor', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live() 
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('vendor_contact_id', null))
                                    ->disabled(fn (?PurchaseOrder $record) => $record && $record->status !== 'Draft'),

                                Forms\Components\Select::make('pic_id')
                                    ->label('PIC Procurement / Purchasing')
                                    ->relationship('pic', 'name') // Menggunakan relasi 'pic' yang dibuat di model
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled(fn (?PurchaseOrder $record) => $record && $record->status !== 'Draft'),

                                // --- 3. PIC VENDOR (DEPENDENT) ---
                                Forms\Components\Select::make('vendor_contact_id')
                                    ->label('PIC Vendor')
                                    ->relationship('contact', 'pic_name')
                                    ->options(function (Forms\Get $get) {
                                        $vendorId = $get('vendor_id');
                                        if (! $vendorId) return [];
                                        
                                        return VendorContact::where('vendor_id', $vendorId)
                                            ->pluck('pic_name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder(fn (Forms\Get $get) => empty($get('vendor_id')) ? 'Pilih Vendor Terlebih Dahulu' : 'Pilih PIC')
                                    ->disabled(fn (?PurchaseOrder $record) => $record && $record->status !== 'Draft')
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('pic_name')->label('Nama PIC')->required(),
                                        Forms\Components\TextInput::make('phone')->label('No HP'),
                                        Forms\Components\TextInput::make('email')->email(),
                                        Forms\Components\Hidden::make('vendor_id')
                                            ->default(fn (Forms\Get $get) => $get('vendor_id')),
                                    ])
                                    ->createOptionUsing(function (array $data, Forms\Get $get) {
                                        if (empty($data['vendor_id'])) {
                                            $data['vendor_id'] = $get('vendor_id');
                                        }
                                        return VendorContact::create($data)->getKey();
                                    }),

                                Forms\Components\Select::make('sales_order_id')
                                    ->label('Referensi SO')
                                    ->relationship('salesOrder', 'so_number')
                                    ->searchable()
                                    ->helperText('Jika PO ini untuk memenuhi SO tertentu')
                                    ->disabledOn('edit')
                                    ->dehydrated(),

                                // --- STATUS ---
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'Draft' => 'Draft',
                                        'Ordered' => 'Dipesan',
                                        'Partial' => 'Diterima Sebagian',
                                        'Received' => 'Diterima Penuh',
                                        'Cancelled' => 'Dibatalkan',
                                        'Billed' => 'Tagihan Diterima',
                                        'Paid' => 'Lunas',
                                    ])
                                    ->default('Draft')
                                    ->required()
                                    ->disabledOn('edit')
                                    ->dehydrated(),
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
                Tables\Columns\TextColumn::make('po_number')->label('No. PO')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->icon('heroicon-m-building-office')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('vendor.name')->searchable()->sortable(),
                
                Tables\Columns\TextColumn::make('contact.pic_name')
                    ->label('PIC Vendor')
                    ->icon('heroicon-m-user-circle')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-')
                    ->formatStateUsing(fn ($state, PurchaseOrder $record) => 
                        $state . ($record->contact?->phone ? ' - ' . $record->contact->phone : '')
                    ),

                Tables\Columns\TextColumn::make('pic.name')
                    ->label('PIC Procurement')
                    ->icon('heroicon-m-user')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('salesOrder.so_number')
                    ->label('Ref SO')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->iconPosition('after')
                    ->color('gray')
                    ->badge()
                    ->weight('bold')
                    ->url(fn (PurchaseOrder $record): ?string => 
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
                    // Logic pencarian khusus relasi HasMany (PurchaseOrder -> Items -> Product)
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('items.product', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('grand_total')->money('IDR')->weight('bold'),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Draft' => 'Draft',
                        'Ordered' => 'Dipesan',
                        'Partial' => 'Diterima Sebagian',
                        'Received' => 'Diterima Penuh',
                        'Cancelled' => 'Dibatalkan',
                        'Billed' => 'Tagihan Diterima',
                        'Paid' => 'Lunas',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'Ordered' => 'warning',
                        'Partial' => 'info',
                        'Received' => 'success',
                        'Cancelled' => 'danger',
                        'Billed' => 'primary',
                        'Paid' => 'success',
                        default => 'gray',
                    }),

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
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status PO')
                    ->options([
                        'Draft' => 'Draft',
                        'Ordered' => 'Dipesan',
                        'Partial' => 'Diterima Sebagian',
                        'Received' => 'Diterima Penuh',
                        'Cancelled' => 'Dibatalkan',
                        'Billed' => 'Tagihan Diterima',
                        'Paid' => 'Lunas',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    
                    // 1. ACTION KIRIM KE VENDOR
                    Tables\Actions\Action::make('send_to_vendor')
                        ->label(fn (PurchaseOrder $record) => $record->status === 'Ordered' ? 'Kirim Ulang' : 'Kirim ke Vendor')
                        ->icon(fn (PurchaseOrder $record) => $record->status === 'Ordered' ? 'heroicon-o-arrow-path' : 'heroicon-o-paper-airplane')
                        ->color(fn (PurchaseOrder $record) => $record->status === 'Ordered' ? 'warning' : 'primary')
                        ->requiresConfirmation()
                        ->modalHeading(fn (PurchaseOrder $record) => $record->status === 'Ordered' ? 'Kirim Ulang PO?' : 'Kirim PO ke Vendor?')
                        ->modalDescription('Status akan diperbarui menjadi "Dipesan".')
                        ->modalSubmitActionLabel('Ya, Kirim')
                        ->action(function (PurchaseOrder $record) {
                            $isResend = $record->status === 'Ordered';
                            $record->update(['status' => 'Ordered']);
                            
                            Notification::make()
                                ->title($isResend ? 'PO Dikirim Ulang' : 'PO Dikirim ke Vendor')
                                ->body('Status dokumen diperbarui menjadi Dipesan.')
                                ->success()
                                ->send()
                                ->sendToDatabase(auth()->user());
                        })
                        ->visible(fn (PurchaseOrder $record) => in_array($record->status, ['Draft', 'Ordered'])),

                   // 2. ACTION TERIMA BARANG (PARTIAL/FULL + REJECT + OVER DELIVERY HANDLING)
                   Tables\Actions\Action::make('receive_goods')
                   ->label('Terima Barang')
                   ->icon('heroicon-o-archive-box-arrow-down')
                   ->color('success')
                   ->modalHeading('Penerimaan Barang')
                   ->modalDescription('Jika jumlah diterima MELEBIHI sisa pesanan, kelebihannya akan otomatis dibuatkan Retur (Over Delivery).')
                   ->modalWidth('5xl') 
                   ->form(function (PurchaseOrder $record) {
                       $remainingItems = $record->remaining_items; 

                       if ($remainingItems->isEmpty()) {
                           return [
                               Forms\Components\Placeholder::make('info')
                                   ->content('Semua barang dalam PO ini sudah diterima sepenuhnya.')
                                   ->extraAttributes(['class' => 'text-success-600 font-bold']),
                           ];
                       }

                       $itemFields = [];
                       foreach ($remainingItems as $item) {
                           $itemFields[] = Forms\Components\Group::make([
                               Forms\Components\Hidden::make("items.{$item['product_id']}.product_id")
                                   ->default($item['product_id']),
                               
                               Forms\Components\Grid::make(12)->schema([
                                   // NAMA PRODUK
                                   Forms\Components\TextInput::make("items.{$item['product_id']}.product_name")
                                       ->label('Produk')
                                       ->default($item['product_name'])
                                       ->disabled()
                                       ->dehydrated(false)
                                       ->columnSpan(4),

                                   // SISA PESANAN (Info saja)
                                   Forms\Components\TextInput::make("items.{$item['product_id']}.qty_remaining_display")
                                       ->label('Sisa PO')
                                       ->default($item['qty_remaining'])
                                       ->disabled()
                                       ->dehydrated(false)
                                       ->columnSpan(2),
                                   
                                   // HIDDEN REAL REMAINING (Untuk validasi di backend)
                                   Forms\Components\Hidden::make("items.{$item['product_id']}.qty_remaining_real")
                                       ->default($item['qty_remaining']),

                                   // DITERIMA BAGUS (BISA LEBIH DARI SISA)
                                   Forms\Components\TextInput::make("items.{$item['product_id']}.qty_received")
                                       ->label('Diterima (Bagus)')
                                       ->numeric()
                                       ->default($item['qty_remaining']) 
                                       ->minValue(0)
                                       // ->maxValue(...)  <-- HAPUS INI AGAR BISA LEBIH
                                       ->required()
                                       ->columnSpan(2)
                                       ->reactive()
                                       ->helperText(function (Forms\Get $get, $state) use ($item) {
                                           $val = (int) $state;
                                           $max = $item['qty_remaining'];
                                           if ($val > $max) {
                                               $excess = $val - $max;
                                               return "Kelebihan {$excess} unit akan diretur otomatis.";
                                           }
                                           return null;
                                       }),

                                   // DITOLAK MANUAL (RUSAK/CACAT)
                                   Forms\Components\TextInput::make("items.{$item['product_id']}.qty_rejected_manual")
                                       ->label('Ditolak (Rusak)')
                                       ->numeric()
                                       ->default(0)
                                       ->minValue(0)
                                       ->live(onBlur: true)
                                       ->columnSpan(2),
                                   
                                   // ALASAN REJECT MANUAL
                                   Forms\Components\Select::make("items.{$item['product_id']}.rejection_reason")
                                       ->label('Alasan Kerusakan')
                                       ->options([
                                           'Damaged' => 'Barang Rusak / Cacat',
                                           'Wrong Item' => 'Salah Kirim Barang',
                                           'Wrong Spec' => 'Spesifikasi Tidak Sesuai',
                                           'Expired' => 'Kadaluarsa',
                                           'Other' => 'Lainnya',
                                       ])
                                       ->placeholder('Pilih Alasan...')
                                       ->columnSpan(12)
                                       ->visible(fn (Forms\Get $get) => (int) $get("items.{$item['product_id']}.qty_rejected_manual") > 0)
                                       ->required(fn (Forms\Get $get) => (int) $get("items.{$item['product_id']}.qty_rejected_manual") > 0),
                               ]),
                           ])->columnSpanFull();
                       }

                       return [
                           Forms\Components\Section::make('Info Penerimaan')
                               ->schema([
                                   Forms\Components\Select::make('warehouse_id')
                                       ->label('Gudang Penerima')
                                       ->options(\App\Models\Warehouse::pluck('name', 'id'))
                                       ->searchable()
                                       ->required()
                                       ->default(1), 
                                   
                                   Forms\Components\TextInput::make('vendor_delivery_number')
                                       ->label('No. Surat Jalan Vendor')
                                       ->required(),
                                   
                                   Forms\Components\DatePicker::make('date')
                                       ->label('Tanggal Terima')
                                       ->default(now())
                                       ->required(),
                               ])->columns(3),

                           Forms\Components\Section::make('Daftar Barang Masuk')
                               ->schema($itemFields),
                       ];
                   })
                   ->action(function (PurchaseOrder $record, array $data) {
                       if (!isset($data['items'])) return;

                       $gr = DB::transaction(function () use ($record, $data) {
                           // A. Buat Header GR
                           $gr = GoodsReceive::create([
                               'gr_number' => 'GR-' . now()->format('ymd') . '-' . rand(1000, 9999),
                               'date' => $data['date'],
                               'purchase_order_id' => $record->id,
                               'vendor_id' => $record->vendor_id,
                               'vendor_contact_id' => $record->vendor_contact_id,
                               'company_id' => $record->company_id,
                               'vendor_delivery_number' => $data['vendor_delivery_number'],
                               'warehouse_id' => $data['warehouse_id'], 
                               'status' => 'Received',
                               'created_by' => auth()->id(),
                           ]);

                           $hasReceivedAny = false;
                           $hasRejectedAny = false; 
                           $rejectedItemsPayload = []; // Penampung Retur (Rusak + Kelebihan)
                           $touchedSalesOrderIds = [];

                           foreach ($data['items'] as $productId => $itemData) {
                               $inputReceived = (int) ($itemData['qty_received'] ?? 0); // Input Fisik Bagus
                               $inputRejected = (int) ($itemData['qty_rejected_manual'] ?? 0); // Input Rusak
                               $reasonManual = $itemData['rejection_reason'] ?? null;
                               $qtyRemainingPO = (int) ($itemData['qty_remaining_real'] ?? 0); // Sisa Jatah PO

                               if ($inputReceived === 0 && $inputRejected === 0) continue;
                               $hasReceivedAny = true;

                               // --- LOGIKA OVER DELIVERY ---
                               $qtyToStock = 0;
                               $qtyOverDelivery = 0;

                               if ($inputReceived > $qtyRemainingPO) {
                                   // Jika input lebih besar dari sisa PO
                                   $qtyToStock = $qtyRemainingPO; // Masuk stok cuma sampai mentok PO
                                   $qtyOverDelivery = $inputReceived - $qtyRemainingPO; // Sisanya Retur
                               } else {
                                   // Jika normal / kurang
                                   $qtyToStock = $inputReceived;
                                   $qtyOverDelivery = 0;
                               }

                               // Total Rejected (Manual Rusak + Kelebihan)
                               $totalRejected = $inputRejected + $qtyOverDelivery;

                               // Ambil data PO Item untuk referensi
                               $poItem = $record->items()->where('product_id', $productId)->first();
                               
                               // B. Simpan GR Item
                               $gr->items()->create([
                                   'product_id' => $productId,
                                   'qty_ordered' => $poItem ? $poItem->qty : 0,
                                   'qty_received' => $qtyToStock, // Yang diakui masuk stok (Sesuai PO)
                                   'qty_rejected' => $totalRejected, // Total Ditolak (Rusak + Lebih)
                                   'rejection_reason' => $totalRejected > 0 ? ($reasonManual ?: 'Over Delivery / Mixed') : null,
                               ]);

                               // --- KUMPULKAN DATA RETUR ---
                               
                               // 1. Retur Manual (Rusak)
                               if ($inputRejected > 0) {
                                   $hasRejectedAny = true;
                                   $rejectedItemsPayload[] = [
                                       'product_id' => $productId,
                                       'qty' => $inputRejected,
                                       'reason' => $reasonManual ?? 'Barang Rusak',
                                   ];
                               }

                               // 2. Retur Otomatis (Kelebihan)
                               if ($qtyOverDelivery > 0) {
                                   $hasRejectedAny = true;
                                   $rejectedItemsPayload[] = [
                                       'product_id' => $productId,
                                       'qty' => $qtyOverDelivery,
                                       'reason' => 'Over Delivery (Kelebihan Kirim)',
                                   ];
                               }

                               // C. UPDATE STOK FISIK (Hanya Qty Yang Diakui / Sesuai PO)
                               if ($qtyToStock > 0) {
                                   $existingStock = ProductStock::where('product_id', $productId)
                                       ->where('warehouse_id', $data['warehouse_id'])
                                       ->where('company_id', $record->company_id)
                                       ->first();

                                   if ($existingStock) {
                                       $existingStock->increment('quantity', $qtyToStock);
                                   } else {
                                       ProductStock::create([
                                           'product_id'   => $productId,
                                           'warehouse_id' => $data['warehouse_id'],
                                           'company_id'   => $record->company_id,
                                           'quantity'     => $qtyToStock,
                                       ]);
                                   }

                                   // D. AUTO-RESERVATION SO
                                   $directSalesOrderId = $record->sales_order_id;
                                   $pendingSOItemsQuery = SalesOrderItem::query()
                                       ->where('product_id', $productId)
                                       ->whereNull('reserved_at');

                                   if ($directSalesOrderId) {
                                       $pendingSOItemsQuery->where('sales_order_id', $directSalesOrderId);
                                   } else {
                                       $pendingSOItemsQuery->whereHas('salesOrder', function ($q) use ($record) {
                                           $q->where('company_id', $record->company_id) 
                                             ->where('status', 'New')
                                             ->orderBy('date', 'asc'); 
                                       });
                                   }

                                   $pendingSOItems = $pendingSOItemsQuery->get();
                                   $qtyAvailableForBooking = $qtyToStock;

                                   foreach ($pendingSOItems as $soItem) {
                                       if ($qtyAvailableForBooking <= 0) break;

                                       if ($qtyAvailableForBooking >= $soItem->qty) {
                                           $soItem->update([
                                               'warehouse_id' => $data['warehouse_id'],
                                               'reserved_at' => now(),
                                           ]);
                                           
                                           $qtyAvailableForBooking -= $soItem->qty;
                                           
                                           if (!in_array($soItem->sales_order_id, $touchedSalesOrderIds)) {
                                               $touchedSalesOrderIds[] = $soItem->sales_order_id;
                                           }
                                       }
                                   }
                               }
                           }

                           if (!$hasReceivedAny) {
                               $gr->delete();
                                Notification::make()->title('Tidak ada barang yang diproses.')->warning()->send();
                                return null;
                           }

                           // F. CREATE PURCHASE RETURN AUTOMATICALLY (RUSAK + LEBIH)
                           if ($hasRejectedAny && count($rejectedItemsPayload) > 0) {
                               $return = \App\Models\PurchaseReturn::create([
                                   'return_number' => 'RET-' . now()->format('ymd') . '-' . rand(1000, 9999),
                                   'date' => $data['date'],
                                   'purchase_order_id' => $record->id,
                                   'vendor_id' => $record->vendor_id,
                                   'company_id' => $record->company_id,
                                   'goods_receive_id' => $gr->id,
                                   'notes' => 'Auto-generated (Reject/Over Delivery)',
                                   'status' => 'Draft',
                               ]);

                               foreach ($rejectedItemsPayload as $rejItem) {
                                   $return->items()->create($rejItem);
                               }

                               Notification::make()
                                   ->title('Retur Pembelian Terbuat')
                                   ->body("Retur #{$return->return_number} dibuat untuk barang rusak/kelebihan.")
                                   ->warning()
                                   ->send()
                                   ->sendToDatabase(auth()->user());
                           }

                           // G. UPDATE STATUS SO
                           if ($record->sales_order_id && !in_array($record->sales_order_id, $touchedSalesOrderIds)) {
                               $touchedSalesOrderIds[] = $record->sales_order_id;
                           }

                           foreach ($touchedSalesOrderIds as $soId) {
                               $salesOrder = SalesOrder::find($soId);
                               if ($salesOrder && in_array($salesOrder->status, ['New', 'Processed'])) {
                                   $hasPendingItems = $salesOrder->items()->whereNull('reserved_at')->exists();
                                   if (!$hasPendingItems) {
                                       $salesOrder->update(['status' => 'Siap Kirim']);
                                       Notification::make()->title('SO Siap Kirim')->body("Stok SO {$salesOrder->so_number} lengkap.")->success()->sendToDatabase($salesOrder->sales);
                                   }
                               }
                           }

                           $record->refresh();
                           $isFullyReceived = $record->remaining_items->isEmpty();
                           $record->update(['status' => $isFullyReceived ? 'Received' : 'Partial']);
                           
                           Notification::make()->title('Penerimaan Barang Berhasil')->success()->send()->sendToDatabase(auth()->user());

                           return $gr; 
                       });

                       if ($gr) {
                            return redirect()->to(GoodsReceiveResource::getUrl('edit', ['record' => $gr->id]));
                       }
                   })
                   ->visible(fn (PurchaseOrder $record) => in_array($record->status, ['Ordered', 'Partial'])),

                    // 3. ACTION INPUT TAGIHAN (BILL)
                    Tables\Actions\Action::make('create_bill')
                        ->label('Input Tagihan (Bill)')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Rekam Tagihan Vendor & Jurnal Otomatis')
                        ->form([
                            Forms\Components\TextInput::make('invoice_number')
                                ->label('Nomor Faktur Vendor')
                                ->required(),
                            Forms\Components\DatePicker::make('due_date')
                                ->label('Jatuh Tempo')
                                ->default(now()->addDays(30))
                                ->required(),
                        ])
                        ->action(function (PurchaseOrder $record, array $data) {
                            return DB::transaction(function () use ($record, $data) {
                                
                                $invoice = \App\Models\PurchaseInvoice::create([
                                    'invoice_number' => $data['invoice_number'],
                                    'date' => now(),
                                    'due_date' => $data['due_date'],
                                    'purchase_order_id' => $record->id,
                                    'vendor_id' => $record->vendor_id,
                                    'company_id' => $record->company_id,
                                    'grand_total' => $record->grand_total,
                                    'status' => 'Unpaid',
                                ]);

                                foreach ($record->items as $item) {
                                    $invoice->items()->create([
                                        'product_id' => $item->product_id,
                                        'qty' => $item->qty,
                                        'unit_price' => $item->unit_price,
                                        'subtotal' => $item->subtotal,
                                    ]);
                                }

                                $inventoryAccount = \App\Models\Account::where('company_id', $record->company_id)
                                    ->where('code', '1-2010')->first(); 
                                
                                $apAccount = \App\Models\Account::where('company_id', $record->company_id)
                                    ->where('code', '2-1101')->first(); 

                                if ($inventoryAccount && $apAccount) {
                                    $journal = \App\Models\Journal::create([
                                        'company_id'   => $record->company_id,
                                        'journal_date' => now(),
                                        'reference'    => $invoice->invoice_number,
                                        'source'       => 'Purchase Invoice',
                                        'memo'         => "Tagihan Pembelian - {$record->vendor->name} (PO: {$record->po_number})",
                                    ]);

                                    $journal->lines()->create([
                                        'account_id' => $inventoryAccount->id,
                                        'direction'  => 'debit',
                                        'amount'     => $invoice->grand_total,
                                        'note'       => 'Penerimaan Stok Barang',
                                    ]);

                                    $journal->lines()->create([
                                        'account_id' => $apAccount->id,
                                        'direction'  => 'credit',
                                        'amount'     => $invoice->grand_total,
                                        'note'       => 'Hutang Dagang Vendor',
                                    ]);
                                } else {
                                    Notification::make()
                                        ->title('Jurnal Gagal Dibuat')
                                        ->body('Akun 1-2010 atau 2-1101 tidak ditemukan.')
                                        ->warning()
                                        ->send()
                                        ->sendToDatabase(auth()->user());
                                }

                                $record->update(['status' => 'Billed']); 
                                $record->refresh();

                                Notification::make()->title('Tagihan & Jurnal Berhasil Direkam')->success()->send()->sendToDatabase(auth()->user());

                                return redirect()->to(PurchaseInvoiceResource::getUrl('edit', ['record' => $invoice->id]));
                            });
                        })
                        ->visible(fn (PurchaseOrder $record) => 
                            in_array($record->status, ['Partial', 'Received', 'Ordered']) && 
                            !\App\Models\PurchaseInvoice::where('purchase_order_id', $record->id)->exists()
                        ),

                    Tables\Actions\Action::make('print')
                        ->label('Cetak PDF')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->url(fn (PurchaseOrder $record) => route('print.po', $record))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('traceability')
                        ->label('Alur Dokumen')
                        ->icon('heroicon-o-arrows-right-left')
                        ->color('info')
                        ->modalSubmitAction(false) 
                        ->modalCancelActionLabel('Tutup')
                        ->modalContent(fn (PurchaseOrder $record) => view('filament.components.po-traceability', ['record' => $record])),

                    Tables\Actions\EditAction::make()
                        ->hidden(fn (PurchaseOrder $record) => in_array($record->status, ['Received', 'Billed', 'Paid', 'Cancelled'])),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (PurchaseOrder $record) => $record->status === 'Draft'),

                ])
                ->label('Aksi')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('info')
                ->tooltip('Menu Pilihan'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (Tables\Actions\DeleteBulkAction $action, \Illuminate\Support\Collection $records) {
                            $records->each(function ($record) {
                                if ($record->status === 'Draft') {
                                    $record->delete();
                                }
                            });
                        }),
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}