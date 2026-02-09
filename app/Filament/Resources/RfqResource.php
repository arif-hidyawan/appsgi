<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RfqResource\Pages;
use App\Filament\Resources\RfqResource\RelationManagers;
use App\Models\Rfq;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Quotation; 
use App\Models\QuotationItem; 
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Concerns\HasPermissionPrefix;
// Import Model Manual & Str
use App\Models\SystemNotification;
use Illuminate\Support\Str;

class RfqResource extends Resource
{
    protected static ?string $model = Rfq::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $modelLabel = 'RFQ';
    protected static ?string $pluralModelLabel = 'RFQ';
    protected static ?int $navigationSort = 1;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'rfq';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Umum RFQ')
                            ->schema([
                                Forms\Components\TextInput::make('rfq_number')
                                    ->label('Nomor RFQ')
                                    ->required()
                                    ->default('RFQ-' . now()->format('Ymd') . '-' . rand(100, 999))
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50),
                                
                                Forms\Components\DatePicker::make('date')
                                    ->label('Tanggal RFQ')
                                    ->default(now())
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),

                                Forms\Components\DatePicker::make('deadline')
                                    ->label('Deadline Purchasing')
                                    ->helperText('Batas waktu staf purchasing untuk melengkapi harga.')
                                    ->native(false)
                                    ->displayFormat('d M Y')
                                    ->suffixIcon('heroicon-m-calendar-days')
                                    ->required(), // Set required jika wajib diisi

                                    Forms\Components\Select::make('sales_id')
                                    ->label('Sales Staff')
                                    // Tambahkan closure query pada parameter ke-3
                                    ->relationship('sales', 'name', fn (Builder $query) => 
                                        $query->whereHas('roles', fn ($q) => $q->where('name', 'Sales'))
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('company_id', null)),
                                
                                Forms\Components\Select::make('purchasing_id')
                                    ->label('PIC Purchasing')
                                    // Tambahkan closure query pada parameter ke-3
                                    ->relationship('purchasing', 'name', fn (Builder $query) => 
                                        $query->whereHas('roles', fn ($q) => $q->where('name', 'Purchasing'))
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Pilih staf purchasing yang bertanggung jawab mencari harga.'),

                                Forms\Components\Select::make('customer_id')
                                    ->label('Customer')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('customer_contact_id', null)),
    
                                Forms\Components\Select::make('customer_contact_id')
                                    ->label('PIC Customer / Kontak')
                                    ->options(function (Forms\Get $get) {
                                        $customerId = $get('customer_id');
                                        if (! $customerId) return []; 
                                        return \App\Models\CustomerContact::where('customer_id', $customerId)
                                            ->get()
                                            ->mapWithKeys(fn ($contact) => [$contact->id => $contact->pic_name . ($contact->phone ? ' - ' . $contact->phone : '')]);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder(fn (Forms\Get $get) => empty($get('customer_id')) ? 'Pilih Customer Terlebih Dahulu' : 'Pilih PIC')
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('pic_name')->required()->label('Nama PIC'),
                                        Forms\Components\TextInput::make('position')->label('Jabatan'),
                                        Forms\Components\TextInput::make('phone')->label('No HP'),
                                        Forms\Components\TextInput::make('email')->email()->label('Email'),
                                        Forms\Components\Hidden::make('customer_id')->default(fn (Forms\Get $get) => $get('customer_id')),
                                    ])
                                    ->createOptionUsing(function (array $data, Forms\Get $get) {
                                        if (empty($data['customer_id'])) $data['customer_id'] = $get('customer_id');
                                        return \App\Models\CustomerContact::create($data)->getKey();
                                    }),          

                                Forms\Components\Select::make('company_id')
                                    ->label('Perusahaan')
                                    ->helperText('Perusahaan yang terkait dengan Sales terpilih.')
                                    ->options(function (Forms\Get $get) {
                                        $salesId = $get('sales_id');
                                        if (! $salesId) return []; 
                                        return \App\Models\Company::whereHas('users', fn ($query) => $query->where('users.id', $salesId))->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder(fn (Forms\Get $get) => empty($get('sales_id')) ? 'Pilih Sales Terlebih Dahulu' : 'Pilih Perusahaan'),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'Draft' => 'Draft',
                                        'Partial' => 'Partial',
                                        'Selesai' => 'Selesai',
                                    ])
                                    ->default('Draft')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated()
                                    ->native(false),

                                // --- UPDATE: MULTIPLE FILE UPLOAD ---
                                Forms\Components\FileUpload::make('attachment')
                                    ->label('Bukti / Ref Customer')
                                    ->helperText('Bisa upload banyak file. Foto, PDF, atau Dokumen (Max 5MB per file)')
                                    ->multiple()     // <--- Mengizinkan banyak file
                                    ->reorderable()  // <--- Mengizinkan urutan diubah
                                    ->directory('rfq-attachments')
                                    ->preserveFilenames()
                                    ->openable()
                                    ->downloadable()
                                    ->maxSize(5120)
                                    ->columnSpan(1),
                                    
                                Forms\Components\Textarea::make('customer_reference')
                                    ->label('Ref / Bukti Customer (Teks)')
                                    ->placeholder('Cth: No. PO / Pesan WA / Email')
                                    ->maxLength(255) 
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])->columns(3),
                        
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
                    ])
                    ->columnSpanFull()
                    ->disabled(fn (?Rfq $record) => $record && $record->status === 'Selesai'),
            ]);
    }

    public static function getRfqItemSchema(): array
    {
        return [
            Forms\Components\Select::make('product_id')
                ->label('Produk')
                ->relationship('product', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->item_code} - {$record->name}")
                ->searchable()
                ->preload()
                ->required()
                ->columnSpanFull()
                ->createOptionModalHeading('Buat Master Produk Baru')
                ->createOptionForm([
                    Forms\Components\Group::make()->schema([
                        Forms\Components\TextInput::make('item_code')
                            ->label('Kode Item')
                            ->default(function() {
                                $lastId = \App\Models\Product::max('id') ?? 0;
                                $nextId = $lastId + 1;
                                return 'ITM-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
                            })
                            ->required()
                            ->unique('products', 'item_code'),
                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required()->label('Nama Kategori'),
                            ]), 
                    ])->columns(2),
                    Forms\Components\TextInput::make('name')->label('Nama Produk')->required(),
                    Forms\Components\FileUpload::make('image')->label('Foto Produk')->image()->directory('product-images')->columnSpanFull(),
                ])
                ->createOptionUsing(function (array $data) {
                    return \App\Models\Product::create($data)->getKey();
                })
                ->live()
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    if (! $state) {
                        $set('vendor_id', null);
                        $set('cost_price', 0);
                        return;
                    }
                    $lastPo = \App\Models\PurchaseOrderItem::query()
                        ->where('product_id', $state)
                        ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
                        ->orderBy('purchase_orders.date', 'desc')
                        ->select('purchase_orders.vendor_id', 'purchase_order_items.unit_price')
                        ->first();

                    if ($lastPo) {
                        $set('vendor_id', $lastPo->vendor_id);
                        $set('cost_price', $lastPo->unit_price);
                        Notification::make()->title('Data Vendor Terakhir Ditemukan')->success()->send();
                    } else {
                        $set('vendor_id', null);
                        $set('cost_price', 0);
                    }
                }),

            Forms\Components\Grid::make(3)
                ->schema([
                    Forms\Components\Group::make([
                        Forms\Components\TextInput::make('qty')
                            ->label('Qty')
                            ->numeric()
                            ->default(1)
                            ->required(),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('show_history')
                                ->label('Lihat Riwayat Harga')
                                ->icon('heroicon-m-clock')
                                ->color('info')
                                ->size('xs')
                                ->modalHeading('Riwayat Harga Vendor')
                                ->modalDescription('Pilih vendor dari riwayat pembelian sebelumnya.')
                                ->modalSubmitAction(false)
                                ->modalContent(function (Forms\Get $get, \Filament\Forms\Components\Actions\Action $action) { 
                                    $productId = $get('product_id');
                                    if (!$productId) return view('filament.components.empty-state', ['message' => 'Pilih Produk terlebih dahulu.']);

                                    $history = \App\Models\PurchaseOrderItem::query()
                                        ->where('product_id', $productId)
                                        ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
                                        ->join('vendors', 'purchase_orders.vendor_id', '=', 'vendors.id')
                                        ->orderBy('purchase_orders.date', 'desc')
                                        ->select('purchase_orders.vendor_id', 'vendors.name as vendor_name', 'purchase_order_items.unit_price', 'purchase_orders.date')
                                        ->limit(10)->get();

                                    $avgData = null;
                                    if ($history->count() > 0) {
                                        $last3 = $history->take(3);
                                        $avgData = ['price' => $last3->avg('unit_price'), 'vendor_id' => $history->first()->vendor_id, 'count' => $last3->count()];
                                    }

                                    return view('filament.components.vendor-history-modal', [
                                        'history' => $history, 'avgData' => $avgData, 'action' => $action
                                    ]);
                                })
                                ->action(function (array $arguments, Forms\Set $set) {
                                    if (isset($arguments['vendor_id'])) $set('vendor_id', $arguments['vendor_id']);
                                    if (isset($arguments['cost_price'])) $set('cost_price', $arguments['cost_price']);
                                    Notification::make()->title('Data Terpilih')->success()->send();
                                }),
                        ]),
                    ])->columnSpan(1),

                    Forms\Components\Select::make('vendor_id')
                        ->label('Pilih Vendor')
                        ->relationship('vendor', 'name')
                        ->searchable()
                        ->preload()
                        ->columnSpan(2)
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->label('Nama Vendor')->required(),
                            Forms\Components\TextInput::make('phone')->label('Telepon'),
                            Forms\Components\TextInput::make('email')->email(),
                        ])
                        ->createOptionUsing(fn (array $data) => \App\Models\Vendor::create($data)->getKey())
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            $productId = $get('product_id');
                            if (!$state || !$productId) return;
                            $lastVendorPrice = \App\Models\PurchaseOrderItem::query()
                                ->where('product_id', $productId)
                                ->whereHas('purchaseOrder', fn($q) => $q->where('vendor_id', $state))
                                ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
                                ->orderBy('purchase_orders.date', 'desc')
                                ->value('unit_price');
                            if ($lastVendorPrice) $set('cost_price', $lastVendorPrice);
                        }),

                    Forms\Components\TextInput::make('cost_price')
                        ->label('HPP Final')
                        ->helperText('Otomatis dari history')
                        ->numeric()
                        ->default(0)
                        ->prefix('Rp')
                        ->required()
                        ->live()
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('selling_price')
                        ->label('Harga Jual (Target)')
                        ->helperText('Target harga jual')
                        ->numeric()
                        ->default(0)
                        ->prefix('Rp')
                        ->required()
                        ->live(onBlur: true)
                        ->columnSpan(1),

                    Forms\Components\Placeholder::make('profit')
                        ->label('Est. Laba (%)')
                        ->content(function (Forms\Get $get) {
                            $hpp = (float) $get('cost_price');
                            $jual = (float) $get('selling_price');
                            if ($hpp > 0 && $jual >= $hpp) {
                                $persen = (($jual - $hpp) / $hpp) * 100;
                                return number_format($persen, 2) . '%';
                            } elseif ($hpp > 0 && $jual < $hpp) {
                                return 'Rugi';
                            }
                            return '-';
                        })
                        ->columnSpan(1),
                ]),

            Forms\Components\Textarea::make('notes')
                ->label('Catatan Item / Spesifikasi')
                ->placeholder('Contoh: Warna Merah, Garansi 1 Tahun, dsb.')
                ->rows(2)
                ->columnSpanFull(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rfq_number')
                    ->label('No RFQ')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d F Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('deadline')
                    ->label('Deadline')
                    ->date('d F Y')
                    ->sortable()
                    ->color(fn ($record) => 
                        ($record->deadline && $record->deadline->isPast() && $record->status !== 'Selesai') 
                        ? 'danger' 
                        : 'gray'
                    )
                    ->icon(fn ($record) => 
                        ($record->deadline && $record->deadline->isPast() && $record->status !== 'Selesai') 
                        ? 'heroicon-m-exclamation-triangle' 
                        : null
                    ),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('contact.pic_name')
                    ->label('PIC Customer')
                    ->icon('heroicon-m-user-circle')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('sales.name')
                    ->label('Sales')
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->sortable(),

                    Tables\Columns\TextColumn::make('purchasing.name')
                    ->label('Purchasing')
                    ->icon('heroicon-m-shopping-cart')
                    ->placeholder('Belum Ditunjuk')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->icon('heroicon-m-building-office-2')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                    Tables\Columns\TextColumn::make('items.product.name')
                    ->label('Item Produk')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->badge()
                    ->color('gray')
                    // Logic pencarian khusus relasi HasMany (RFQ -> Items -> Product)
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('items.product', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('completeness')
                    ->label('Kelengkapan')
                    ->state(function (Rfq $record) {
                        $items = $record->items;
                        $total = $items->count();
                        if ($total === 0) return '0%';
                        $completed = $items->filter(fn ($item) => !empty($item->vendor_id) && $item->cost_price > 0 && $item->selling_price > 0)->count();
                        return round(($completed / $total) * 100) . '%';
                    })
                    ->description(function (Rfq $record) {
                        $items = $record->items;
                        $total = $items->count();
                        if ($total === 0) return '-';
                        $completed = $items->filter(fn($item) => !empty($item->vendor_id) && $item->cost_price > 0 && $item->selling_price > 0)->count();
                        return "{$completed} dari {$total} Item";
                    })
                    ->badge()
                    ->color(function (string $state) {
                        $percentage = (int) rtrim($state, '%');
                        if ($percentage === 100) return 'success';
                        if ($percentage === 0) return 'danger';
                        return 'warning';
                    })
                    ->icon(fn (string $state) => (int) rtrim($state, '%') === 100 ? 'heroicon-m-check-circle' : 'heroicon-m-clock'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(function (Rfq $record) {
                        if ($record->status !== 'Draft') return $record->status;
                        $items = $record->items;
                        if ($items->isEmpty()) return 'Draft Purchasing';
                        
                        $hasDraftItems = $items->contains(function ($item) {
                            return empty($item->vendor_id) || (float) $item->cost_price <= 0;
                        });

                        return $hasDraftItems ? 'Draft Purchasing' : 'Draft Admin';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Draft Purchasing' => 'gray',
                        'Draft Admin'      => 'info',
                        'Partial'          => 'warning',
                        'Selesai'          => 'success',
                        default            => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Draft Purchasing' => 'heroicon-m-magnifying-glass',
                        'Draft Admin'      => 'heroicon-m-currency-dollar',
                        'Partial'          => 'heroicon-m-clock',
                        'Selesai'          => 'heroicon-m-check-badge',
                        default            => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('updater.name')
                    ->label('Diubah Oleh')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Draft' => 'Draft',
                        'Partial' => 'Partial',
                        'Selesai' => 'Selesai',
                    ]),
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('sales_id')
                    ->label('Sales Staff')
                    ->relationship('sales', 'name'),
                    Tables\Filters\SelectFilter::make('purchasing_id')
                    ->label('PIC Purchasing')
                    ->relationship('purchasing', 'name'),
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    
                    // --- 1. CREATE QUOTATION ---
                    Tables\Actions\Action::make('create_quotation')
                        ->icon('heroicon-o-document-plus')
                        ->color(function (Rfq $record) {
                            $alreadyQuotedProductIds = \App\Models\QuotationItem::query()
                                ->whereHas('quotation', fn($q) => $q->whereHas('rfqs', fn($r) => $r->where('rfqs.id', $record->id)))
                                ->pluck('product_id')->toArray();

                            $remainingItems = $record->items->whereNotIn('product_id', $alreadyQuotedProductIds);
                            
                            if ($remainingItems->isEmpty()) return 'gray';

                            $allReady = $remainingItems->every(fn($item) => 
                                $item->vendor_id && $item->cost_price > 0 && $item->selling_price > 0
                            );

                            return $allReady ? 'success' : 'warning';
                        })
                        ->label(function (Rfq $record) {
                            $alreadyQuotedProductIds = \App\Models\QuotationItem::query()
                                ->whereHas('quotation', fn($q) => $q->whereHas('rfqs', fn($r) => $r->where('rfqs.id', $record->id)))
                                ->pluck('product_id')->toArray();

                            $remainingItems = $record->items->whereNotIn('product_id', $alreadyQuotedProductIds);
                            $readyCount = $remainingItems->filter(fn($item) => $item->vendor_id && $item->cost_price > 0 && $item->selling_price > 0)->count();
                            $totalRemaining = $remainingItems->count();

                            if ($totalRemaining === 0) return 'Quotation Selesai';
                            if ($readyCount === $totalRemaining && $readyCount > 0) return 'Buat Quotation (Full)';
                            return 'Buat Quotation (Parsial)';
                        })
                        ->visible(function (Rfq $record) {
                            if ($record->status === 'Selesai') return false;
                            $alreadyQuotedProductIds = \App\Models\QuotationItem::query()
                                ->whereHas('quotation', fn($q) => $q->whereHas('rfqs', fn($r) => $r->where('rfqs.id', $record->id)))
                                ->pluck('product_id')->toArray();
                            
                            return $record->items()
                                ->whereNotIn('product_id', $alreadyQuotedProductIds)
                                ->whereNotNull('vendor_id')
                                ->where('cost_price', '>', 0)
                                ->where('selling_price', '>', 0)
                                ->exists();
                        })
                        ->form(function (Rfq $record) {
                            $alreadyQuotedProductIds = \App\Models\QuotationItem::query()
                                ->whereHas('quotation', fn($q) => $q->whereHas('rfqs', fn($r) => $r->where('rfqs.id', $record->id)))
                                ->pluck('product_id')->toArray();
                            
                            $remainingItems = $record->items->whereNotIn('product_id', $alreadyQuotedProductIds);
                            $readyItems = $remainingItems->filter(fn($item) => $item->vendor_id && $item->cost_price > 0 && $item->selling_price > 0);

                            if ($readyItems->isEmpty()) {
                                return [
                                    Forms\Components\Placeholder::make('info')
                                        ->content('Belum ada item yang datanya lengkap (Vendor & Harga) untuk diproses.')
                                        ->extraAttributes(['class' => 'text-danger-600 font-bold']),
                                ];
                            }

                            $options = $readyItems->mapWithKeys(fn($item) => [$item->id => "{$item->product->name} (Qty: {$item->qty}) - Jual: Rp " . number_format($item->selling_price, 0,',','.')]);

                            return [
                                Forms\Components\CheckboxList::make('selected_items')
                                    ->label('Pilih Item Siap Proses')
                                    ->options($options)
                                    ->default($options->keys()->toArray())
                                    ->required()
                                    ->columns(1)
                                    ->helperText('Hanya menampilkan item yang datanya sudah lengkap (Vendor & Harga terisi).'),
                            ];
                        })
                        ->action(function (array $data, Rfq $record) {
                            if (empty($data['selected_items'])) {
                                Notification::make()->title('Gagal')->body('Tidak ada item yang dipilih.')->danger()->send();
                                return;
                            }
                        
                            $quotation = \App\Models\Quotation::create([
                                'quotation_number' => 'QT-' . now()->format('ymd') . '-' . rand(1000, 9999),
                                'date' => now(),
                                'customer_id' => $record->customer_id,
                                'customer_contact_id' => $record->customer_contact_id,
                                'sales_id' => $record->sales_id,
                                'company_id' => $record->company_id, 
                                'status' => 'Draft',
                                'grand_total' => 0, 
                            ]);

                            $quotation->rfqs()->attach($record->id);
                        
                            $selectedRfqItemIds = $data['selected_items'];
                            $grandTotal = 0;
                            $rfqItems = $record->items()->whereIn('id', $selectedRfqItemIds)->get();
                        
                            foreach ($rfqItems as $rfqItem) {
                                $subtotal = $rfqItem->qty * $rfqItem->selling_price;
                                $quotation->items()->create([
                                    'product_id' => $rfqItem->product_id,
                                    'vendor_id'  => $rfqItem->vendor_id, 
                                    'cost_price' => $rfqItem->cost_price, 
                                    'notes' => $rfqItem->notes,
                                    'qty' => $rfqItem->qty,
                                    'lead_time'  => $rfqItem->lead_time,
                                    'unit_price' => $rfqItem->selling_price, 
                                    'subtotal' => $subtotal,
                                ]);
                                $grandTotal += $subtotal;
                            }
                        
                            $quotation->update(['grand_total' => $grandTotal]);
                        
                            $totalRfqItemsCount = $record->items()->count();
                            $totalQuotedItemsCount = \App\Models\QuotationItem::query()
                                ->whereHas('quotation', fn($q) => $q->whereHas('rfqs', fn($r) => $r->where('rfqs.id', $record->id)))
                                ->distinct('product_id') 
                                ->count('product_id');
                        
                            if ($totalQuotedItemsCount >= $totalRfqItemsCount) {
                                $record->update(['status' => 'Selesai']);
                                Notification::make()->title('RFQ Selesai (Full Quotation)')->body("Quotation {$quotation->quotation_number} berhasil dibuat.")->success()->send()->sendToDatabase(auth()->user());
                            } else {
                                $record->update(['status' => 'Partial']);
                                Notification::make()->title('Quotation Parsial Berhasil Dibuat')->body("Quotation {$quotation->quotation_number} dibuat untuk sebagian item.")->success()->send()->sendToDatabase(auth()->user());
                            }
                        
                            return redirect()->to(\App\Filament\Resources\QuotationResource::getUrl('edit', ['record' => $quotation->id]));
                        }),

                    // --- 2. NOTIFIKASI KE ADMIN ---
                    Tables\Actions\Action::make('notify_admin')
                        ->label('Kirim Notif ke Admin')
                        ->icon('heroicon-o-bell-alert')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Pengiriman Notifikasi')
                        ->modalDescription('Apakah Anda yakin data RFQ ini sudah lengkap dan siap diperiksa Admin?')
                        ->visible(function (Rfq $record) {
                            if ($record->status !== 'Draft') return false;
                            $items = $record->items;
                            if ($items->isEmpty()) return false;
                            $hasIncompleteItems = $items->contains(fn ($item) => empty($item->vendor_id) || (float) $item->cost_price <= 0);
                            return !$hasIncompleteItems;
                        })
                        ->action(function (Rfq $record) {
                            // 1. CARI USER DENGAN ROLE 'Admin'
                            $recipients = \App\Models\User::role('Admin')->get(); 
    
                            if ($recipients->isEmpty()) {
                                Notification::make()->title('Gagal: Tidak ada Admin')->danger()->send();
                                return;
                            }
    
                            // 2. SIAPKAN FORMAT DATA STANDAR FILAMENT (MANUAL ARRAY)
                            $notificationData = [
                                'title' => 'RFQ Menunggu Approval',
                                'body' => "RFQ #{$record->rfq_number} menunggu review.",
                                'icon' => 'heroicon-o-document-check',
                                'icon_color' => 'warning',
                                'status' => 'warning', 
                                'duration' => 'persistent', 
                                'view' => 'filament-notifications::notification', 
                                'view_data' => [],
                                'actions' => [
                                    [
                                        'name' => 'view',
                                        'label' => 'Buka RFQ',
                                        'url' => RfqResource::getUrl('edit', ['record' => $record->id]),
                                        'color' => 'primary',
                                        'view' => 'filament-actions::button-action',
                                    ]
                                ],
                            ];
    
                            // 3. INSERT MANUAL KE DATABASE (AGAR PASTI MASUK & SYNC LONCENG)
                            $successCount = 0;
                            foreach ($recipients as $user) {
                                \App\Models\SystemNotification::create([
                                    'id' => (string) \Illuminate\Support\Str::uuid(),
                                    'type' => 'Filament\Notifications\DatabaseNotification', // <--- Tipe ini dikenali Lonceng
                                    'notifiable_type' => 'App\Models\User',
                                    'notifiable_id' => $user->id,
                                    'data' => $notificationData, // <--- Masukkan Data Format Manual
                                    'read_at' => null,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                                $successCount++;
                            }
    
                            // 4. Notifikasi Sukses ke Pengirim (Toast)
                            Notification::make()
                                ->title('Berhasil Dikirim')
                                ->body("Notifikasi terkirim ke {$successCount} Admin.")
                                ->success()
                                ->send();
                        }),

                    // --- 3. DUPLIKAT ---
                    Tables\Actions\ReplicateAction::make()
                        ->label('Duplikat RFQ')
                        ->icon('heroicon-m-square-2-stack')
                        ->color('warning')
                        ->excludeAttributes(['items_count']) 
                        ->modalHeading('Duplikat RFQ')
                        ->modalDescription('Duplikat RFQ ini? Nomor baru akan dibuat, harga di-reset.')
                        ->modalSubmitActionLabel('Ya, Duplikat')
                        ->beforeReplicaSaved(function (Rfq $replica) {
                            $replica->rfq_number = 'RFQ-' . now()->format('Ymd') . '-' . rand(100, 999);
                            $replica->date = now();
                            $replica->status = 'Draft';
                        })
                        ->after(function (Rfq $original, Rfq $replica) {
                            foreach ($original->items as $item) {
                                $newItem = $item->replicate();
                                $newItem->rfq_id = $replica->id;
                                $newItem->selected_option = null; 
                                $newItem->cost_price = 0;        
                                $newItem->selling_price = 0;      
                                $newItem->vendor_id = null;       
                                foreach ([1, 2, 3] as $i) {
                                    $newItem->{"price_{$i}"} = null;
                                    $newItem->{"vendor_{$i}_id"} = null;
                                }
                                $newItem->save();
                            }
                            Notification::make()->title('RFQ Berhasil Diduplikasi')->body("Nomor Baru: {$replica->rfq_number}")->success()->send()->sendToDatabase(auth()->user());
                        }),
    
                    // --- 4. TRACEABILITY ---
                    Tables\Actions\Action::make('traceability')
                        ->label('Alur Dokumen')
                        ->icon('heroicon-o-arrows-right-left')
                        ->color('gray')
                        ->modalSubmitAction(false) 
                        ->modalCancelActionLabel('Tutup')
                        ->modalContent(fn (Rfq $record) => view('filament.components.rfq-traceability', ['record' => $record])),
    
                    Tables\Actions\EditAction::make()->hidden(fn (Rfq $record) => $record->status === 'Selesai'),
                    Tables\Actions\DeleteAction::make()->hidden(fn (Rfq $record) => in_array($record->status, ['Partial', 'Selesai'])),
                ])
                ->label('Aksi')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('info')
                ->tooltip('Menu Pilihan'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('merge_to_quotation')
                        ->label('Merge Jadi 1 Quotation')
                        ->icon('heroicon-o-rectangle-stack')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $first = $records->first();
                            foreach ($records as $record) {
                                if ($record->customer_id !== $first->customer_id || $record->company_id !== $first->company_id) {
                                    Notification::make()->title('Gagal Merge')->body('Customer/Perusahaan beda.')->danger()->send();
                                    return;
                                }
                            }
    
                            $quotation = \App\Models\Quotation::create([
                                'quotation_number' => 'QT-MRG-' . now()->format('ymd') . '-' . rand(100, 999),
                                'date' => now(),
                                'customer_id' => $first->customer_id,
                                'customer_contact_id' => $first->customer_contact_id,
                                'sales_id' => $first->sales_id,
                                'company_id' => $first->company_id,
                                'status' => 'Draft',
                                'grand_total' => 0,
                                'notes' => 'Gabungan dari RFQ: ' . $records->pluck('rfq_number')->implode(', '),
                            ]);

                            $quotation->rfqs()->attach($records->pluck('id'));
                            $grandTotal = 0;
                            $anyItemAdded = false;
    
                            foreach ($records as $rfq) {
                                $readyItems = $rfq->items->filter(fn($i) => $i->vendor_id && $i->cost_price > 0 && $i->selling_price > 0);
                                foreach ($readyItems as $item) {
                                    $subtotal = $item->qty * $item->selling_price;
                                    $quotation->items()->create([
                                        'product_id' => $item->product_id,
                                        'vendor_id' => $item->vendor_id,
                                        'cost_price' => $item->cost_price,
                                        'notes' => $item->notes,
                                        'qty' => $item->qty,
                                        'lead_time'  => $item->lead_time,
                                        'unit_price' => $item->selling_price, 
                                        'subtotal' => $subtotal,
                                    ]);
                                    $grandTotal += $subtotal;
                                    $anyItemAdded = true;
                                }
                                if ($readyItems->isNotEmpty()) {
                                    $totalRfqItems = $rfq->items->count();
                                    $quotedItemsCount = \App\Models\QuotationItem::query()
                                        ->whereHas('quotation', fn($q) => $q->whereHas('rfqs', fn($r) => $r->where('rfqs.id', $rfq->id)))
                                        ->distinct('product_id')->count('product_id');
                                    $newStatus = ($quotedItemsCount >= $totalRfqItems) ? 'Selesai' : 'Partial';
                                    $rfq->update(['status' => $newStatus]);
                                }
                            }
    
                            if (!$anyItemAdded) {
                                $quotation->delete(); 
                                Notification::make()->title('Gagal')->body('Tidak ada item lengkap.')->danger()->send();
                                return;
                            }
    
                            $quotation->update(['grand_total' => $grandTotal]);
                            Notification::make()->title('Merge Berhasil')->success()->send()->sendToDatabase(auth()->user());
                            return redirect()->to(\App\Filament\Resources\QuotationResource::getUrl('edit', ['record' => $quotation->id]));
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
            'index' => Pages\ListRfqs::route('/'),
            'create' => Pages\CreateRfq::route('/create'),
            'edit' => Pages\EditRfq::route('/{record}/edit'),
        ];
    }
}