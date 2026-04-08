<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceiveGoodsResource\Pages;
use App\Models\PurchaseOrder;
use App\Models\GoodsReceive;
use App\Models\ProductStock;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Warehouse;
use App\Models\Account; 
use App\Models\Journal; 
use App\Filament\Resources\GoodsReceiveResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReceiveGoodsResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;
    protected static ?string $slug = 'inventory/terima-barang'; 

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Terima Barang (PO)';
    protected static ?string $modelLabel = 'Penerimaan Barang';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('status', ['Ordered', 'Partial']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]); 
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('No. PO')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('date')
                    ->label('Tgl Order')
                    ->date('d M Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Vendor Pengirim')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('items.custom_name')
                    ->label('Item Dipesan')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(function (string $state, $record) {
                        return !empty($state) ? $state : ($record->items->firstWhere('custom_name', null)?->product?->name ?? '-');
                    })
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('items', function ($q) use ($search) {
                            $q->where('custom_name', 'like', "%{$search}%")
                              ->orWhereHas('product', function ($q2) use ($search) {
                                  $q2->where('name', 'like', "%{$search}%");
                              });
                        });
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Ordered' => 'Menunggu Kedatangan', 
                        'Partial' => 'Diterima Sebagian',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Ordered' => 'warning',
                        'Partial' => 'info',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('vendor_id')
                    ->label('Filter Vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('receive_goods')
                    ->label('Proses Terima')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('success')
                    ->button()
                    ->modalHeading('Penerimaan Fisik Barang')
                    ->modalDescription('Cek fisik barang dan sesuaikan dengan surat jalan vendor.')
                    ->modalWidth('5xl') 
                    ->form(function (PurchaseOrder $record) {
                        $remainingItems = $record->remaining_items; 

                        if ($remainingItems->isEmpty()) {
                            return [
                                Forms\Components\Placeholder::make('info')
                                    ->content('Semua barang dalam PO ini sudah diterima.')
                                    ->extraAttributes(['class' => 'text-success-600 font-bold']),
                            ];
                        }

                        $itemFields = [];
                        foreach ($remainingItems as $item) {
                            $itemFields[] = Forms\Components\Group::make([
                                Forms\Components\Hidden::make("items.{$item['product_id']}.product_id")
                                    ->default($item['product_id']),
                                
                                Forms\Components\Grid::make(12)->schema([
                                    Forms\Components\TextInput::make("items.{$item['product_id']}.product_name")
                                        ->label('Produk')
                                        ->default($item['product_name'])
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpan(4),

                                    Forms\Components\TextInput::make("items.{$item['product_id']}.qty_remaining_display")
                                        ->label('Sisa PO')
                                        ->default($item['qty_remaining'])
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpan(2),
                                    
                                    Forms\Components\Hidden::make("items.{$item['product_id']}.qty_remaining_real")
                                        ->default($item['qty_remaining']),

                                    Forms\Components\TextInput::make("items.{$item['product_id']}.qty_received")
                                        ->label('Diterima (Bagus)')
                                        ->numeric()
                                        ->default($item['qty_remaining']) 
                                        ->minValue(0)
                                        ->required()
                                        ->columnSpan(2)
                                        ->reactive(),

                                    Forms\Components\TextInput::make("items.{$item['product_id']}.qty_rejected_manual")
                                        ->label('Ditolak (Rusak)')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->live(onBlur: true)
                                        ->columnSpan(2),
                                    
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
                                        ->options(Warehouse::pluck('name', 'id'))
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

                            Forms\Components\Section::make('Daftar Cek Fisik Barang')
                                ->schema($itemFields),
                        ];
                    })
                    ->action(function (PurchaseOrder $record, array $data) {
                        if (!isset($data['items'])) return;

                        $gr = DB::transaction(function () use ($record, $data) {
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

                            $totalValueReceived = 0;
                            $totalTaxValue = 0;
                            $hasReceivedAny = false;
                            $hasRejectedAny = false; 
                            $rejectedItemsPayload = []; 
                            $touchedSalesOrderIds = [];

                            foreach ($data['items'] as $productId => $itemData) {
                                $inputReceived = (int) ($itemData['qty_received'] ?? 0); 
                                $inputRejected = (int) ($itemData['qty_rejected_manual'] ?? 0); 
                                $qtyRemainingPO = (int) ($itemData['qty_remaining_real'] ?? 0);

                                if ($inputReceived === 0 && $inputRejected === 0) continue;
                                $hasReceivedAny = true;

                                $qtyToStock = ($inputReceived > $qtyRemainingPO) ? $qtyRemainingPO : $inputReceived;
                                $qtyOverDelivery = ($inputReceived > $qtyRemainingPO) ? ($inputReceived - $qtyRemainingPO) : 0;
                                $totalRejected = $inputRejected + $qtyOverDelivery;

                                $poItem = $record->items()->where('product_id', $productId)->first();
                                
                                // Hitung Nilai untuk Jurnal (Hanya barang yang masuk stok)
                                if ($poItem) {
                                    $unitPrice = $poItem->unit_price;
                                    $totalValueReceived += ($qtyToStock * $unitPrice);
                                    // Asumsi tax rate dari PO item jika ada
                                    $totalTaxValue += ($qtyToStock * $unitPrice * ($poItem->tax_rate / 100 ?? 0));
                                }

                                $gr->items()->create([
                                    'product_id' => $productId,
                                    'qty_ordered' => $poItem ? $poItem->qty : 0,
                                    'qty_received' => $qtyToStock, 
                                    'qty_rejected' => $totalRejected, 
                                    'rejection_reason' => $totalRejected > 0 ? ($itemData['rejection_reason'] ?? 'Over Delivery') : null,
                                ]);

                                // Update Stok
                                if ($qtyToStock > 0) {
                                    ProductStock::updateOrCreate(
                                        [
                                            'product_id' => $productId,
                                            'warehouse_id' => $data['warehouse_id'],
                                            'company_id' => $record->company_id
                                        ],
                                        ['quantity' => DB::raw("quantity + $qtyToStock")]
                                    );
                                }
                                
                                // Alokasi otomatis stok ke SO yang berstatus "New"
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

                            if (!$hasReceivedAny) {
                                $gr->delete();
                                Notification::make()->title('Tidak ada barang yang diproses.')->warning()->send();
                                return null;
                            }

                            // Generate Retur Otomatis jika ada barang rusak/lebih
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
                            }

                            // Update SO status ke 'Siap Kirim' jika semua item terpenuhi
                            if ($record->sales_order_id && !in_array($record->sales_order_id, $touchedSalesOrderIds)) {
                                $touchedSalesOrderIds[] = $record->sales_order_id;
                            }

                            foreach ($touchedSalesOrderIds as $soId) {
                                $salesOrder = SalesOrder::find($soId);
                                if ($salesOrder && in_array($salesOrder->status, ['New', 'Processed'])) {
                                    $hasPendingItems = $salesOrder->items()->whereNull('reserved_at')->exists();
                                    if (!$hasPendingItems) {
                                        $salesOrder->update(['status' => 'Siap Kirim']);
                                    }
                                }
                            }

                            // --- LOGIK JURNAL OTOMATIS FIX (MENGGUNAKAN KODE EKSPLISIT) ---
                            // Tembak langsung ke kode akun yang benar agar tidak salah ambil karena flag is_inventory
                            $inventoryAcc = Account::where('company_id', $record->company_id)->where('code', '1105.001')->first(); // Persediaan Barang
                            $taxAcc = Account::where('company_id', $record->company_id)->where('code', '1108.001')->first(); // PPN Masukan
                            $unbilledAcc = Account::where('company_id', $record->company_id)->where('code', '2104.008')->first(); // Hutang Belum Difakturkan

                            if ($inventoryAcc && $unbilledAcc && $totalValueReceived > 0) {
                                $journal = Journal::create([
                                    'company_id' => $record->company_id,
                                    'journal_date' => $data['date'],
                                    'reference' => $gr->gr_number,
                                    'source' => 'Goods Receive',
                                    'memo' => "Penerimaan Barang PO: {$record->po_number} (SJ: {$data['vendor_delivery_number']})",
                                ]);

                                // DEBIT: Persediaan
                                $journal->lines()->create([
                                    'account_id' => $inventoryAcc->id,
                                    'direction' => 'debit',
                                    'amount' => $totalValueReceived,
                                    'note' => 'Penerimaan Persediaan Barang',
                                ]);

                                // DEBIT: PPN Masukan (Jika ada tax)
                                if ($totalTaxValue > 0 && $taxAcc) {
                                    $journal->lines()->create([
                                        'account_id' => $taxAcc->id,
                                        'direction' => 'debit',
                                        'amount' => $totalTaxValue,
                                        'note' => 'PPN Masukan (Estimasi)',
                                    ]);
                                }

                                // KREDIT: Hutang Belum Difakturkan
                                $journal->lines()->create([
                                    'account_id' => $unbilledAcc->id,
                                    'direction' => 'credit',
                                    'amount' => $totalValueReceived + $totalTaxValue,
                                    'note' => 'Hutang Belum Difakturkan',
                                ]);
                            } else {
                                Notification::make()
                                    ->title('Peringatan: Jurnal Gagal Dibuat')
                                    ->body('Pastikan Akun 1105.001 (Persediaan) dan 2104.008 (Hutang Belum Difakturkan) tersedia di menu Chart of Accounts.')
                                    ->warning()
                                    ->send();
                            }

                            $record->update(['status' => ($record->remaining_items->isEmpty() ? 'Received' : 'Partial')]);
                            Notification::make()->title('Penerimaan & Jurnal Berhasil')->success()->send();
                            
                            return $gr; 
                        });

                        if ($gr) return redirect()->to(GoodsReceiveResource::getUrl('edit', ['record' => $gr->id]));
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageReceiveGoods::route('/')];
    }
}