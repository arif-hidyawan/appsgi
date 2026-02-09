<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesOrderResource\Pages;
use App\Filament\Resources\SalesOrderResource\RelationManagers;
use App\Models\SalesOrder;
use App\Models\ProductStock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\PurchaseOrder;
use App\Filament\Resources\PurchaseOrderResource; 
use App\Models\DeliveryOrder;
use App\Filament\Resources\DeliveryOrderResource;
use App\Models\SalesInvoice;
use App\Filament\Resources\SalesInvoiceResource;
use App\Filament\Resources\QuotationResource;
use App\Models\SalesOrderItem;
use Filament\Notifications\Notification;
use App\Filament\Concerns\HasPermissionPrefix;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\Builder;

class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $modelLabel = 'Sales Order';
    protected static ?int $navigationSort = 3;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'sales_order';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Order')
                            ->schema([
                                Forms\Components\TextInput::make('so_number')
                                    ->label('No. SO')
                                    ->default('SO-' . now()->format('Ymd') . '-' . rand(100, 999))
                                    ->required()
                                    ->disabled()
                                    ->afterStateHydrated(fn ($state) => $state)
                                    ->dehydrated()
                                    ->readOnly(),

                                Forms\Components\TextInput::make('customer_po_number')
                                    ->label('No. PO Customer')
                                    ->placeholder('Masukkan Nomor PO dari Customer')
                                    ->required()
                                    ->disabled(fn (?SalesOrder $record) => $record && !in_array($record->status, ['New'])),

                                Forms\Components\DatePicker::make('date')
                                    ->label('Tanggal Order')
                                    ->default(now())
                                    ->required()
                                    ->disabled(fn (?SalesOrder $record) => $record && !in_array($record->status, ['New'])),
                                
                                Forms\Components\Select::make('quotations') 
                                    ->label('Ref Quotation(s)')
                                    ->relationship('quotations', 'quotation_number')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->disabled() 
                                    ->dehydrated(false),

                                // --- TAMBAHAN SALES STAFF (Agar terlihat data dari Quotation) ---
                                Forms\Components\Select::make('sales_id')
                                    ->label('Sales Staff')
                                    ->relationship('sales', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled(fn (?SalesOrder $record) => $record && !in_array($record->status, ['New'])),

                                    Forms\Components\Select::make('customer_id')
                                    ->relationship('customer', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live() // Aktifkan Live agar bisa trigger event
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $customer = \App\Models\Customer::find($state);
                                            if ($customer) {
                                                // Otomatis isi Payment Terms
                                                if ($customer->payment_terms) {
                                                    $set('payment_terms', $customer->payment_terms);
                                                }
                                                
                                                // --- LOGIC BARU: AMBIL DEFAULT TAX ---
                                                if ($customer->default_tax_id) {
                                                    $set('tax_id', $customer->default_tax_id);
                                                }
                                                // -------------------------------------
                                            }
                                        }
                                    })
                                    ->disabled(fn (?SalesOrder $record) => $record && $record->status !== 'New'),

                                Forms\Components\Select::make('company_id')
                                    ->label('Perusahaan (Internal)')
                                    ->relationship('company', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->disabled() 
                                    ->dehydrated(),

                                Forms\Components\Select::make('status')
                                    ->label('Status Order')
                                    ->options([
                                        'New' => 'Baru',
                                        'Processed' => 'Diproses (Menunggu PO)',
                                        'Siap Kirim' => 'Siap Kirim (Stok Ready)',
                                        'Completed' => 'Selesai (Dikirim)',
                                        'Invoiced' => 'Sudah Ditagihkan',
                                        'Paid' => 'Lunas',
                                        'Cancelled' => 'Dibatalkan',
                                    ])
                                    ->default('New')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(),
                                    Forms\Components\TextInput::make('payment_terms')
                                    ->label('Termin Pembayaran')
                                    ->placeholder('Pilih atau ketik manual...')
                                    ->datalist([
                                        'Cash', 'COD', 'CBD (Cash Before Delivery)', 
                                        'Net 7', 'Net 14', 'Net 30', 'Net 45', 'Net 60', 
                                        'DP 50% - Pelunasan Saat Barang Siap',
                                    ])
                                    ->required(),
                                    

                                  
Forms\Components\Select::make('tax_id')
    ->label('Pajak (PPN)')
    ->relationship('tax', 'name') // Pastikan ada relasi 'tax' di model SalesOrder
    ->searchable()
    ->preload()
    ->required()
    ->reactive() // Agar bisa trigger update total jika pajak diganti
    ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
        // Logic jika user mengganti pajak secara manual di form SO
        // Kita simpan ID pajaknya, nanti perhitungan total di-handle di ItemsRelationManager atau Observer
        
        // Opsional: Anda bisa memicu perhitungan ulang total di sini jika logicnya ada di frontend,
        // tapi karena logic hitung ada di ItemsRelationManager, perubahan ini baru akan
        // ngefek ke angka total setelah user save/edit item.
    }),
    
                            ])->columns(2), 
                            
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
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                
                Tables\Columns\TextColumn::make('so_number')
                    ->label('No. SO')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->date('d M Y') // Format tanggal biar rapi
                    ->label('Tanggal')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                // Kolom Sales
                Tables\Columns\TextColumn::make('sales.name')
                    ->label('Sales')
                    ->icon('heroicon-m-user')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->icon('heroicon-m-building-office')
                    ->sortable()
                    ->searchable()
                    ->toggleable(), 

                Tables\Columns\TextColumn::make('quotations.quotation_number')
                    ->label('Ref Quotation')
                    ->badge()
                    ->color('gray')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (SalesOrder $record): ?string => 
                        $record->quotations->first() 
                            ? QuotationResource::getUrl('edit', ['record' => $record->quotations->first()->id]) 
                            : null
                    )
                    ->openUrlInNewTab()
                    ->placeholder('-'),

                    Tables\Columns\TextColumn::make('items.product.name') // Ini cuma label header/key
    ->label('Item Produk')
    ->formatStateUsing(function ($state, $record) {
        // Kita ambil item-item dari relasi
        // Karena ini kolom relasi 'items.product.name', $state default-nya adalah nama produk master
        // Tapi kita butuh akses ke pivot/model item-nya untuk cek custom_name
        
        // Cara paling aman di TextColumn List adalah manual mapping
        $items = $record->items; 
        
        if ($items->isEmpty()) return '-';

        return $items->map(function ($item) {
            // LOGIC UTAMA: Pakai custom_name jika ada, kalau tidak pakai nama master product
            $displayName = $item->custom_name ?? $item->product->name;
            
            // Opsional: Tambah kode barang biar jelas
            return "{$displayName} ({$item->product->item_code})";
        })->implode('<br>'); // Gabungkan dengan baris baru
    })
    ->html() // Wajib aktifkan HTML agar <br> terbaca
    ->listWithLineBreaks() // Ini sebenarnya redundant jika kita manual implode, tapi biarkan saja
    ->limitList(3)
    ->expandableLimitedList()
    ->badge()
    ->color('gray')
    ->searchable(query: function ($query, $search) {
        // Logic search harus mencakup custom_name juga
        return $query->whereHas('items', function ($q) use ($search) {
            $q->where('custom_name', 'like', "%{$search}%")
              ->orWhereHas('product', fn($prod) => $prod->where('name', 'like', "%{$search}%"));
        });
    })
    ->toggleable(),

                Tables\Columns\TextColumn::make('customer_po_number')
                    ->label('PO Cust')
                    ->icon('heroicon-m-document-text')
                    ->color('gray')
                    ->badge()
                    ->searchable()
                    ->placeholder('-'),

                
                    Tables\Columns\TextColumn::make('subtotal_amount')
    ->label('Subtotal')
    ->money('IDR')
    ->state(function (SalesOrder $record) {
        // Hitung manual dari items biar pasti akurat
        return $record->items->sum('subtotal');
    })
    ->sortable(),
                
                Tables\Columns\TextColumn::make('tax_amount')
                    ->label('PPN')
                    ->money('IDR')
                    ->color('danger') // Kasih warna merah biar ngeh ini pajak
                    ->sortable(),
                

                // --- UPDATE: GRAND TOTAL DENGAN SUMMARIZER ---
                Tables\Columns\TextColumn::make('grand_total')
                    ->money('IDR')
                    ->weight('bold')
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->money('IDR')
                            ->label('Total Penjualan')
                    ), // <--- INI YG BIKIN TOTAL DI BAWAH

                    Tables\Columns\TextColumn::make('payment_terms')
                    ->label('Termin')
                    ->badge()
                    ->color('info')
                    ->sortable(),

              

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'New' => 'Baru',
                        'Processed' => 'Diproses',
                        'Siap Kirim' => 'Siap Kirim',
                        'Completed' => 'Selesai',
                        'Invoiced' => 'Sudah Ditagihkan',
                        'Paid' => 'Lunas',
                        'Cancelled' => 'Dibatalkan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'New' => 'info',
                        'Processed' => 'warning',
                        'Siap Kirim' => 'success',
                        'Completed' => 'primary',
                        'Invoiced' => 'success',
                        'Paid' => 'success',
                        'Cancelled' => 'danger',
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
            ->defaultSort('id', 'desc')
            ->filters([
                // --- FILTER BARU: PERIODE TANGGAL ---
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date) => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date) => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['date_from'] ?? null) {
                            $indicators['date_from'] = 'Dari: ' . \Carbon\Carbon::parse($data['date_from'])->format('d M Y');
                        }
                        if ($data['date_to'] ?? null) {
                            $indicators['date_to'] = 'Sampai: ' . \Carbon\Carbon::parse($data['date_to'])->format('d M Y');
                        }
                        return $indicators;
                    }),

                // Filter Lainnya
                Tables\Filters\SelectFilter::make('sales_id')
                    ->label('Sales Staff')
                    ->relationship('sales', 'name')
                    ->searchable()
                    ->preload(),

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
                
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Order')
                    ->options([
                        'New' => 'Baru',
                        'Processed' => 'Diproses',
                        'Siap Kirim' => 'Siap Kirim',
                        'Completed' => 'Selesai',
                        'Invoiced' => 'Sudah Ditagihkan',
                        'Paid' => 'Lunas',
                        'Cancelled' => 'Dibatalkan',
                    ]),
            ])
            ->actions([
                // ... (Action group tetap sama, tidak perlu diubah) ...
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('print')
                        ->label('Cetak Sales Order')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->url(fn (SalesOrder $record) => route('print.so', $record))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('generate_po')
                        ->label('Order Kekurangan (PO)')
                        ->icon('heroicon-o-shopping-cart')
                        ->color('danger') 
                        ->modalHeading('Buat Purchase Order')
                        ->modalDescription('Pilih PIC Purchasing yang akan menangani Purchase Order ini.')
                        ->visible(function (SalesOrder $record) {
                            if ($record->status !== 'New') return false;
                            foreach ($record->items as $item) {
                                if ($item->reserved_at) continue;
                                $physicalStock = ProductStock::where('company_id', $record->company_id)->where('product_id', $item->product_id)->sum('quantity');
                                $reservedStock = SalesOrderItem::where('product_id', $item->product_id)->whereNotNull('reserved_at')
                                    ->whereHas('salesOrder', fn($q) => $q->where('company_id', $record->company_id))->sum('qty');
                                if (($physicalStock - $reservedStock) < $item->qty) return true;
                            }
                            return false; 
                        })
                        ->form([
                            Forms\Components\Select::make('pic_id')
                                ->label('PIC Purchasing')
                                ->options(\App\Models\User::pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->default(auth()->id()),
                        ])
                        ->action(function (SalesOrder $record, array $data) {
                            $rfqs = \App\Models\Rfq::whereHas('quotations', function ($q) use ($record) {
                                $q->whereHas('salesOrders', fn ($so) => $so->where('sales_orders.id', $record->id));
                            })->with('items')->get();

                            if ($rfqs->isEmpty()) {
                                Notification::make()->title('Gagal')->body('Tidak ada referensi RFQ untuk ambil harga beli.')->danger()->send();
                                return;
                            }

                            $allRfqItems = $rfqs->pluck('items')->flatten();

                            $poData = [];
                            foreach ($record->items as $soItem) {
                                if ($soItem->reserved_at) continue;
                                $physicalStock = ProductStock::where('company_id', $record->company_id)->where('product_id', $soItem->product_id)->sum('quantity');
                                $reservedStock = SalesOrderItem::where('product_id', $soItem->product_id)->whereNotNull('reserved_at')
                                    ->whereHas('salesOrder', fn($q) => $q->where('company_id', $record->company_id))->sum('qty');
                                $qtyToBuy = max(0, $soItem->qty - ($physicalStock - $reservedStock));

                                if ($qtyToBuy > 0) {
                                    $rfqItem = $allRfqItems->where('product_id', $soItem->product_id)
                                        ->whereNotNull('vendor_id')
                                        ->first(); 

                                    if ($rfqItem && $rfqItem->vendor_id) {
                                        $poData[$rfqItem->vendor_id][] = [
                                            'product_id' => $soItem->product_id,
                                            'qty' => $qtyToBuy,
                                            'unit_price' => $rfqItem->cost_price, 
                                            'subtotal' => $qtyToBuy * $rfqItem->cost_price,
                                            'lead_time' => $soItem->lead_time,
                                            'notes' => $soItem->notes,
                                        ];
                                    }
                                }
                            }

                            if (empty($poData)) {
                                Notification::make()->title('Info').body('Tidak ada item yang perlu di-order atau data vendor tidak ditemukan.')->warning()->send();
                                return;
                            }

                            $createdPoIds = [];

                            foreach ($poData as $vendorId => $items) {
                                $po = PurchaseOrder::create([
                                    'po_number' => 'PO-' . now()->format('ymd') . '-' . rand(1000, 9999),
                                    'date' => now(),
                                    'vendor_id' => $vendorId,
                                    'sales_order_id' => $record->id,
                                    'company_id' => $record->company_id,
                                    'status' => 'Draft',
                                    'grand_total' => collect($items)->sum('subtotal'),
                                    'pic_id' => $data['pic_id'], 
                                    'created_by' => auth()->id(),
                                ]);
                                
                                foreach ($items as $item) { 
                                    $po->items()->create($item); 
                                }
                                $createdPoIds[] = $po->id;
                            }

                            $record->update(['status' => 'Processed']); 
                            Notification::make()->title('PO Berhasil Dibuat')->success()->send()->sendToDatabase(auth()->user());

                            if (!empty($createdPoIds)) {
                                return redirect()->to(PurchaseOrderResource::getUrl('edit', ['record' => end($createdPoIds)]));
                            }
                        }),

                    Tables\Actions\Action::make('mark_ready')
                        ->label('Set Siap Kirim')
                        ->icon('heroicon-o-check-circle')
                        ->color('success') 
                        ->requiresConfirmation()
                        ->visible(function (SalesOrder $record) {
                            if ($record->status !== 'New') return false;
                            foreach ($record->items as $item) {
                                if ($item->reserved_at === null) return false;
                            }
                            return true; 
                        })
                        ->action(function (SalesOrder $record) {
                            $record->update(['status' => 'Siap Kirim']);
                            Notification::make()->title('Status: Siap Kirim')->success()->send()->sendToDatabase(auth()->user());
                        }),

                    Tables\Actions\Action::make('create_delivery')
                        ->label('Kirim Barang (DO)')
                        ->icon('heroicon-o-truck') 
                        ->color('info')
                        ->modalHeading('Buat Surat Jalan (Delivery Order)')
                        ->modalDescription('Masukkan jumlah barang yang akan dikirim saat ini. Kosongkan atau isi 0 jika item tidak dikirim.')
                        ->form(function (SalesOrder $record) {
                            $formSchema = [];
                            
                            foreach ($record->items as $item) {
                                $alreadyDelivered = \App\Models\DeliveryOrderItem::whereHas('deliveryOrder', function ($q) use ($record) {
                                    $q->where('sales_order_id', $record->id);
                                })->where('product_id', $item->product_id)->sum('qty_delivered');

                                $remainingQty = $item->qty - $alreadyDelivered;

                                if ($remainingQty <= 0) continue;

                                $currentStock = \App\Models\ProductStock::where('company_id', $record->company_id)
                                    ->where('product_id', $item->product_id)
                                    ->sum('quantity');

                                $suggestedQty = min($remainingQty, $currentStock);

                                $formSchema[] = Forms\Components\Group::make([
                                    Forms\Components\TextInput::make("items.{$item->product_id}")
                                        ->label("{$item->product->name}")
                                        ->helperText("Sisa Order: {$remainingQty} | Stok Fisik: {$currentStock}")
                                        ->numeric()
                                        ->default($suggestedQty) 
                                        ->minValue(0)
                                        ->maxValue($remainingQty) 
                                        ->placeholder('Input Qty')
                                        ->required(),
                                    
                                    Forms\Components\Hidden::make("cost_price.{$item->product_id}")
                                        ->default($item->cost_price), 
                                ]);
                            }

                            if (empty($formSchema)) {
                                return [
                                    Forms\Components\Placeholder::make('info')
                                        ->content('Semua barang dalam Sales Order ini sudah dikirim sepenuhnya.')
                                        ->extraAttributes(['class' => 'text-success-600 font-bold']),
                                ];
                            }

                            return $formSchema;
                        })
                        ->action(function (SalesOrder $record, array $data) {
                            $itemsToDeliver = collect($data['items'] ?? [])->filter(fn($qty) => $qty > 0);

                            if ($itemsToDeliver->isEmpty()) {
                                Notification::make()->title('Gagal')->body('Tidak ada jumlah barang yang diinput untuk dikirim.')->warning()->send();
                                return;
                            }

                            return DB::transaction(function () use ($record, $itemsToDeliver, $data) {
                                $do = DeliveryOrder::create([
                                    'do_number' => 'DO-' . now()->format('ymd') . '-' . rand(1000, 9999),
                                    'date' => now(), 
                                    'sales_order_id' => $record->id,
                                    'customer_id' => $record->customer_id,
                                    'company_id' => $record->company_id,
                                    'vehicle_number' => '-', 
                                    'status' => 'Draft', 
                                ]);

                                $totalCostValue = 0;

                                foreach ($itemsToDeliver as $productId => $qty) {
                                    $soItem = $record->items->where('product_id', $productId)->first();
                                    
                                    $do->items()->create([
                                        'product_id' => $productId,
                                        'qty_ordered' => $soItem ? $soItem->qty : $qty, 
                                        'qty_delivered' => $qty, 
                                    ]);

                                    $costPrice = $data['cost_price'][$productId] ?? 0;
                                    $totalCostValue += ($costPrice * $qty);
                                }

                                $hppAcc = \App\Models\Account::where('company_id', $record->company_id)->where('code', '5-1300')->first();
                                $invAcc = \App\Models\Account::where('company_id', $record->company_id)->where('code', '1-2010')->first();

                                if ($hppAcc && $invAcc && $totalCostValue > 0) {
                                    $journal = \App\Models\Journal::create([
                                        'company_id'   => $record->company_id,
                                        'journal_date' => now(),
                                        'reference'    => $do->do_number,
                                        'source'       => 'Delivery Order',
                                        'memo'         => "Jurnal HPP atas pengiriman DO: {$do->do_number} (Ref SO: {$record->so_number})",
                                    ]);

                                    $journal->lines()->create([
                                        'account_id' => $hppAcc->id,
                                        'direction'  => 'debit',
                                        'amount'     => $totalCostValue,
                                        'note'       => 'Beban Pokok Penjualan (Partial)',
                                    ]);

                                    $journal->lines()->create([
                                        'account_id' => $invAcc->id,
                                        'direction'  => 'credit',
                                        'amount'     => $totalCostValue,
                                        'note'       => 'Pengurangan Stok (Partial)',
                                    ]);
                                }

                                $totalOrdered = $record->items->sum('qty');
                                $totalDeliveredAllTime = \App\Models\DeliveryOrderItem::whereHas('deliveryOrder', function ($q) use ($record) {
                                    $q->where('sales_order_id', $record->id);
                                })->sum('qty_delivered');

                                if ($totalDeliveredAllTime >= $totalOrdered) {
                                    $record->update(['status' => 'Completed']);
                                    Notification::make()->title('Pengiriman Selesai (Full)')->success()->send()->sendToDatabase(auth()->user());
                                } else {
                                    $record->update(['status' => 'Processed']); 
                                    Notification::make()->title('Pengiriman Sebagian (Partial) Berhasil')->success()->send()->sendToDatabase(auth()->user());
                                }

                                return redirect()->to(DeliveryOrderResource::getUrl('edit', ['record' => $do->id]));
                            });
                        })
                        ->visible(fn (SalesOrder $record) => in_array($record->status, ['Siap Kirim', 'Processed'])),

                        Tables\Actions\Action::make('create_invoice')
                        ->label('Buat Tagihan (Invoice)')
                        ->icon('heroicon-o-document-currency-dollar')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Terbitkan Invoice & Jurnal Otomatis')
                        ->modalDescription('Sistem akan membuat Invoice, mencatat Jurnal Piutang, dan memperbarui status SO menjadi "Sudah Ditagihkan".')
                        ->form([
                            Forms\Components\DatePicker::make('due_date')
                                ->label('Jatuh Tempo')
                                ->default(now()->addDays(30))
                                ->required(),
                        ])
                        ->action(function (SalesOrder $record, array $data) {
                            return DB::transaction(function () use ($record, $data) {
                                
                                // 1. Buat Header Invoice (BAWA DATA PAJAK)
                                $invoice = SalesInvoice::create([
                                    'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . rand(1000, 9999),
                                    'date' => now(),
                                    'due_date' => $data['due_date'],
                                    'sales_order_id' => $record->id,
                                    'customer_id' => $record->customer_id,
                                    'company_id' => $record->company_id, 
                                    
                                    // --- PERBAIKAN: BAWA DATA VALUE ---
                                    'subtotal_amount' => $record->subtotal_amount, // Jika di invoice ada kolom ini
                                    'tax_amount' => $record->tax_amount,           // PENTING: Bawa nilai pajak
                                    'grand_total' => $record->grand_total,
                                    // ----------------------------------
                                    
                                    'status' => 'Unpaid',
                                ]);
                    
                                // 2. Copy Items (Tetap sama)
                                foreach ($record->items as $item) {
                                    $invoice->items()->create([
                                        'product_id' => $item->product_id,
                                        'qty' => $item->qty,
                                        'unit_price' => $item->unit_price,
                                        'subtotal' => $item->subtotal,
                                    ]);
                                }
                    
                                // 3. Buat Jurnal Akuntansi (Jurnal Balik: Piutang vs Penjualan & PPN Keluaran)
                                // Kita butuh Akun PPN Keluaran (Output VAT)
                                $arAccount = \App\Models\Account::where('company_id', $record->company_id)->where('code', '1-1210')->first(); // Piutang
                                $salesAccount = \App\Models\Account::where('company_id', $record->company_id)->where('code', '4-1100')->first(); // Penjualan
                                $taxAccount = \App\Models\Account::where('company_id', $record->company_id)->where('code', '2-1500')->first(); // Hutang PPN Keluaran (Contoh Kode)
                    
                                if ($arAccount && $salesAccount) {
                                    $journal = \App\Models\Journal::create([
                                        'company_id'   => $record->company_id,
                                        'journal_date' => now(), 
                                        'reference'    => $invoice->invoice_number,
                                        'source'       => 'Sales Invoice',
                                        'memo'         => "Penjualan Kredit - {$record->customer->name} ({$invoice->invoice_number})",
                                    ]);
                    
                                    // A. DEBIT: Piutang Usaha (Sebesar Grand Total)
                                    $journal->lines()->create([
                                        'account_id' => $arAccount->id,
                                        'direction'  => 'debit',
                                        'amount'     => $invoice->grand_total,
                                        'note'       => 'Piutang Penjualan',
                                    ]);
                    
                                    // B. KREDIT: Pendapatan Penjualan (Sebesar Subtotal / DPP)
                                    $journal->lines()->create([
                                        'account_id' => $salesAccount->id,
                                        'direction'  => 'credit',
                                        'amount'     => $record->subtotal_amount, // Masukkan DPP (Subtotal) ke akun pendapatan
                                        'note'       => 'Pendapatan Penjualan',
                                    ]);
                    
                                    // C. KREDIT: Hutang PPN (Sebesar Tax Amount) - Jika ada pajak
                                    if ($record->tax_amount > 0 && $taxAccount) {
                                        $journal->lines()->create([
                                            'account_id' => $taxAccount->id,
                                            'direction'  => 'credit',
                                            'amount'     => $record->tax_amount,
                                            'note'       => 'PPN Keluaran',
                                        ]);
                                    } elseif ($record->tax_amount > 0 && !$taxAccount) {
                                        // Fallback jika akun pajak tidak ketemu, masukkan ke sales (atau throw error)
                                        // Tapi idealnya harus ada akun pajak.
                                        // Notification::make()->title('Warning: Akun PPN tidak ditemukan!')->warning()->send();
                                    }
                                }
                    
                                $record->update(['status' => 'Invoiced']);
                                $record->refresh();
                    
                                Notification::make()->title('Invoice Berhasil Terbit')->success()->send();
                    
                                return redirect()->to(SalesInvoiceResource::getUrl('edit', ['record' => $invoice->id]));
                            });
                        })
                        ->visible(fn (SalesOrder $record) => 
                            $record->status === 'Completed' && 
                            !SalesInvoice::where('sales_order_id', $record->id)->exists()
                        ),

                    Tables\Actions\Action::make('traceability')
                        ->label('Alur Dokumen')
                        ->icon('heroicon-o-arrows-right-left')
                        ->color('info')
                        ->modalSubmitAction(false) 
                        ->modalCancelActionLabel('Tutup')
                        ->modalContent(fn (SalesOrder $record) => view('filament.components.so-traceability', ['record' => $record])),

                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()->visible(fn (SalesOrder $record) => $record->status === 'New'),
                ])
                ->label('Aksi') 
                ->icon('heroicon-m-ellipsis-vertical') 
                ->color('info')
                ->tooltip('Menu Pilihan'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->status === 'New') {
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
            'index' => Pages\ListSalesOrders::route('/'),
            'create' => Pages\CreateSalesOrder::route('/create'),
            'edit' => Pages\EditSalesOrder::route('/{record}/edit'),
        ];
    }
}