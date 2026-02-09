<?php

namespace App\Filament\Resources\RfqResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\RfqResource; 
use Illuminate\Support\Facades\DB; // WAJIB ADA untuk perhitungan DB::raw
use Filament\Tables\Columns\Summarizers\Summarizer; // Ganti Sum dengan Summarizer

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RfqItemTemplateExport;
use App\Exports\ProductDatabaseExport;
use App\Imports\RfqItemsImport;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items'; 

    protected static ?string $title = 'Detail Item Produk';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // ==========================================================
                // BAGIAN 1: DATA UTAMA
                // ==========================================================
                Forms\Components\Section::make('Vendor Terpilih (Utama)')
                    ->description('Data ini yang akan masuk ke kalkulasi RFQ.')
                    ->schema([
                        // --- 1. PILIH PRODUK ---
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
        Forms\Components\Select::make('stock_classification_id')
        ->label('Klasifikasi Stok')
        // Opsi 1: Menggunakan Options query langsung (Paling aman jika belum ada relasi di Model Product)
        ->options(\App\Models\StockClassification::query()->pluck('name', 'id'))
        // Opsi 2: Jika sudah ada relasi 'stockClassification' di Model Product, gunakan:
        // ->relationship('stockClassification', 'name') 
        ->searchable()
        ->preload(),
  
                                ])->columns(3),
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

                        // --- 2. GRID SYSTEM ---
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Group::make([
                                    Forms\Components\TextInput::make('qty')
                                        ->label('Qty')
                                        ->numeric()
                                        ->default(1)
                                        ->required(),

                                    // --- TAMBAHAN: INPUT INDEN (HYBRID) ---
    Forms\Components\TextInput::make('lead_time')
    ->label('Estimasi Inden')
    ->numeric()
    ->default(0) // Default 0 (Ready Stock)
    ->suffix('Hari')
    ->datalist([ // Fitur HTML5: Suggestion List tapi tetap bisa ketik manual
        0, 
        3,
        5, 
        7, 
        14, 
        30, 
        45, 
        60, 
        90
    ])
    ->placeholder('0 = Ready')
    ->required(),
// --------------------------------------

                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('show_history')
                                            ->label('Lihat Riwayat Harga')
                                            ->icon('heroicon-m-clock')
                                            ->color('info')
                                            ->size('xs')
                                            ->modalHeading('Riwayat Harga Vendor')
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
                                                    'history' => $history,
                                                    'avgData' => $avgData,
                                                    'action' => $action
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

                                // 1. HPP FINAL
                                Forms\Components\TextInput::make('cost_price')
                                    ->label('HPP Final')
                                    ->helperText('Otomatis dari history')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->required()
                                    ->live(onBlur: true) 
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        // LOGIKA MARKUP: Jika HPP berubah, hitung ulang Harga Jual
                                        $margin = (float) $get('profit_percentage');
                                        $hpp = (float) $state;

                                        if ($margin > 0 && $margin < 100) {
                                            // Rumus Margin (Gross): Selling = HPP / (1 - Margin%)
                                            $sellingPrice = $hpp / (1 - ($margin / 100));
                                            $set('selling_price', round($sellingPrice));
                                        }
                                    })
                                    ->columnSpan(1),

                                // 2. INPUT MANUAL LABA (%)
                                Forms\Components\TextInput::make('profit_percentage')
                                    ->label('Laba (%)')
                                    ->helperText('Input margin yang diinginkan')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->dehydrated(false)
                                    ->formatStateUsing(function ($record) {
                                        if (!$record || $record->cost_price <= 0 || $record->selling_price <= 0) return 0;
                                        
                                        $hpp = (float) $record->cost_price;
                                        $selling = (float) $record->selling_price;
                                        
                                        // Rumus Gross Margin: ((Jual - HPP) / Jual) * 100
                                        return round((($selling - $hpp) / $selling) * 100, 2);
                                    })
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        // LOGIKA: User input Laba %, Sistem hitung Harga Jual
                                        $hpp = (float) $get('cost_price');
                                        $margin = (float) $state;

                                        if ($hpp > 0 && $margin < 100) {
                                             // Rumus: Selling = HPP / (1 - Margin%)
                                            $sellingPrice = $hpp / (1 - ($margin / 100));
                                            $set('selling_price', round($sellingPrice));
                                        }
                                    })
                                    ->columnSpan(1)
                                    ->hidden(fn () => !Auth::user()->can('rfq.view_price')),

                                // 3. HARGA JUAL (TARGET)
                                Forms\Components\TextInput::make('selling_price')
                                    ->label('Harga Jual (Target)')
                                    ->helperText('Target harga jual')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        // LOGIKA REVERSE: User ubah Harga Jual Manual -> Hitung Laba % nya
                                        $hpp = (float) $get('cost_price');
                                        $selling = (float) $state;

                                        if ($selling > 0 && $hpp > 0) {
                                            // Rumus Gross Margin: ((Jual - HPP) / Jual) * 100
                                            $margin = (($selling - $hpp) / $selling) * 100;
                                            $set('profit_percentage', round($margin, 2));
                                        }
                                    })
                                    ->columnSpan(1)
                                    ->hidden(fn () => !Auth::user()->can('rfq.view_price')),
                            ]),

                        // --- NOTES & PURCHASE LINK ---
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan Item / Spesifikasi')
                            ->placeholder('Contoh: Warna Merah, Garansi 1 Tahun, dsb.')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('purchase_link')
                            ->label('Link Pembelian (Marketplace / Web)')
                            ->placeholder('https://shopee.co.id/product/...')
                            ->prefixIcon('heroicon-m-link')
                            ->url() 
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('openLink')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->url(fn ($state) => $state)
                                    ->openUrlInNewTab()
                                    ->visible(fn ($state) => !empty($state))
                            )
                            ->columnSpanFull(),
                    ]),

                // ==========================================================
                // BAGIAN 2: PERBANDINGAN HARGA
                // ==========================================================
                Forms\Components\Section::make('Perbandingan Harga (Kompetitor / Opsi Lain)')
                    ->description('Masukkan opsi vendor lain. Klik tombol "Pilih" untuk menjadikan opsi tersebut sebagai Vendor Utama.')
                    ->collapsed()
                    ->schema([
                        // --- OPSI 1 ---
                        Forms\Components\Section::make('Opsi Pembanding 1')
                            ->compact()
                            ->headerActions([
                                Forms\Components\Actions\Action::make('use_option_1')
                                    ->label('Pilih Opsi 1 Ini')
                                    ->icon('heroicon-m-arrow-up-circle')
                                    ->color('success')
                                    ->action(function (Forms\Get $get, Forms\Set $set) {
                                        $set('vendor_id', $get('vendor_1_id'));
                                        $set('cost_price', $get('price_1'));
                                        $set('purchase_link', $get('link_1'));
                                        Notification::make()->title('Data Opsi 1 disalin ke Utama')->success()->send();
                                    }),
                            ])
                            ->schema([
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\Select::make('vendor_1_id')
                                        ->label('Vendor 1')
                                        ->relationship('vendor', 'name')
                                        ->searchable()->preload()
                                        ->createOptionForm([Forms\Components\TextInput::make('name')->required()]),
                                    Forms\Components\TextInput::make('price_1')
                                        ->label('HPP 1')
                                        ->numeric()->prefix('Rp'),
                                    Forms\Components\TextInput::make('link_1')
                                        ->label('Link 1')
                                        ->prefixIcon('heroicon-m-link')
                                        ->url(),
                                ]),
                            ]),

                        // --- OPSI 2 ---
                        Forms\Components\Section::make('Opsi Pembanding 2')
                            ->compact()
                            ->headerActions([
                                Forms\Components\Actions\Action::make('use_option_2')
                                    ->label('Pilih Opsi 2 Ini')
                                    ->icon('heroicon-m-arrow-up-circle')
                                    ->color('success')
                                    ->action(function (Forms\Get $get, Forms\Set $set) {
                                        $set('vendor_id', $get('vendor_2_id'));
                                        $set('cost_price', $get('price_2'));
                                        $set('purchase_link', $get('link_2'));
                                        Notification::make()->title('Data Opsi 2 disalin ke Utama')->success()->send();
                                    }),
                            ])
                            ->schema([
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\Select::make('vendor_2_id')
                                        ->label('Vendor 2')
                                        ->relationship('vendor', 'name')
                                        ->searchable()->preload(),
                                    Forms\Components\TextInput::make('price_2')
                                        ->label('HPP 2')
                                        ->numeric()->prefix('Rp'),
                                    Forms\Components\TextInput::make('link_2')
                                        ->label('Link 2')
                                        ->prefixIcon('heroicon-m-link')
                                        ->url(),
                                ]),
                            ]),

                        // --- OPSI 3 ---
                        Forms\Components\Section::make('Opsi Pembanding 3')
                            ->compact()
                            ->headerActions([
                                Forms\Components\Actions\Action::make('use_option_3')
                                    ->label('Pilih Opsi 3 Ini')
                                    ->icon('heroicon-m-arrow-up-circle')
                                    ->color('success')
                                    ->action(function (Forms\Get $get, Forms\Set $set) {
                                        $set('vendor_id', $get('vendor_3_id'));
                                        $set('cost_price', $get('price_3'));
                                        $set('purchase_link', $get('link_3'));
                                        Notification::make()->title('Data Opsi 3 disalin ke Utama')->success()->send();
                                    }),
                            ])
                            ->schema([
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\Select::make('vendor_3_id')
                                        ->label('Vendor 3')
                                        ->relationship('vendor', 'name')
                                        ->searchable()->preload(),
                                    Forms\Components\TextInput::make('price_3')
                                        ->label('HPP 3')
                                        ->numeric()->prefix('Rp'),
                                    Forms\Components\TextInput::make('link_3')
                                        ->label('Link 3')
                                        ->prefixIcon('heroicon-m-link')
                                        ->url(),
                                ]),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        $canViewPrice = Auth::user()->can('rfq.view_price');

        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                Tables\Columns\ImageColumn::make('product.image')
                    ->label('Foto')
                    ->circular()
                    ->disk('public')
                    ->visibility('public'),
                
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk')
                    ->description(function ($record) {
                        $desc = "Code: " . $record->product->item_code;
                        if ($record->notes) {
                            $desc .= " | Note: " . $record->notes;
                        }
                        return $desc;
                    })
                    ->wrap()
                    ->sortable(),

                // --- TAMBAHAN: KOLOM INDEN ---
Tables\Columns\TextColumn::make('lead_time')
->label('Inden')
->badge()
->formatStateUsing(fn ($state) => $state == 0 ? 'Ready' : $state . ' Hari')
->color(fn ($state) => match (true) {
    $state == 0 => 'success', // Hijau jika Ready
    $state <= 7 => 'info',    // Biru jika inden cepat
    $state <= 30 => 'warning',// Kuning jika inden sedang
    default => 'danger',      // Merah jika inden lama (> 30 hari)
})
->alignCenter()
->sortable(),
// -----------------------------
                
                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('purchase_link')
                    ->label('Link Beli')
                    ->icon('heroicon-m-shopping-cart')
                    ->color('info')
                    ->formatStateUsing(fn () => 'Buka Link')
                    ->url(fn ($record) => $record->purchase_link)
                    ->openUrlInNewTab()
                    ->badge()
                    ->toggleable()
                    ->visible(fn ($record) => !empty($record->purchase_link)),

                    Tables\Columns\TextColumn::make('data_status') 
                    ->label('Status Data')
                    ->badge()
                    ->state(function ($record) {
                        $noVendor  = empty($record->vendor_id);
                        $noHpp     = (float) $record->cost_price <= 0;
                        $noSelling = (float) $record->selling_price <= 0;
                
                        // LOGIKA BARU:
                        // 1. Jika Vendor kosong ATAU HPP kosong -> anggap Draft (Sourcing belum beres)
                        if ($noVendor || $noHpp) {
                            return 'Draft';
                        }
                
                        // 2. Jika Vendor & HPP ada, TAPI Harga Jual kosong -> Belum Lengkap (Pricing belum beres)
                        if ($noSelling) {
                            return 'Belum Lengkap';
                        }
                
                        // 3. Semua terisi
                        return 'Lengkap';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Draft'         => 'gray',    // Abu-abu untuk Draft
                        'Belum Lengkap' => 'warning', // Kuning/Orange untuk peringatan harga jual belum ada
                        'Lengkap'       => 'success', // Hijau untuk lengkap
                        default         => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Draft'         => 'heroicon-m-pencil-square',      // Ikon pensil/edit
                        'Belum Lengkap' => 'heroicon-m-exclamation-circle', // Ikon tanda seru
                        'Lengkap'       => 'heroicon-m-check-badge',        // Ikon centang
                        default         => 'heroicon-m-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Vendor Terpilih')
                    ->icon('heroicon-m-building-storefront')
                    ->placeholder('-')
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cost_price')
                    ->label('HPP Final')
                    ->money('IDR')
                    ->weight('bold')
                    ->color(fn ($state) => $state <= 0 ? 'danger' : 'gray'), 
                
                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Jual')
                    ->money('IDR')
                    ->color(fn ($state) => $state <= 0 ? 'danger' : 'gray')
                    ->hidden(! $canViewPrice), 

                Tables\Columns\TextColumn::make('profit_percentage')
                    ->label('Laba (%)')
                    ->state(function ($record) {
                        $hpp = (float) $record->cost_price;
                        $jual = (float) $record->selling_price;
                
                        if ($jual <= 0) return '-'; 
                
                        // RUMUS GROSS MARGIN: ((Jual - HPP) / Jual) * 100
                        return number_format((($jual - $hpp) / $jual) * 100, 1) . '%';
                    })
                    ->badge()
                    ->color(fn ($state) => str_contains($state, '-') ? 'danger' : 'success')
                    ->hidden(! $canViewPrice),

                // --- KOLOM TOTAL (Subtotal & Ringkasan Bawah) ---
                Tables\Columns\TextColumn::make('subtotal_estimation')
                    ->label('Total (Est.)')
                    ->money('IDR')
                    ->weight('bold')
                    ->color('primary')
                    ->state(function ($record) {
                        // Hitung Subtotal per baris (Qty * Jual)
                        return $record->qty * $record->selling_price;
                    })
                    ->hidden(! $canViewPrice)
                    // GUNAKAN SUMMARIZER GENERIC agar tidak error SQL "Column not found"
                    ->summarize(
                        Summarizer::make()
                            ->label('Total Estimasi RFQ')
                            ->money('IDR')
                            // Menggunakan DB::raw untuk menghitung total langsung di database
                            ->using(fn ($query) => $query->sum(DB::raw('qty * selling_price')))
                    ),
            ])
            ->headerActions([
                Tables\Actions\Action::make('downloadProducts')
                    ->label('Download Data Produk')
                    ->icon('heroicon-o-book-open')
                    ->color('info')
                    ->action(fn () => Excel::download(new ProductDatabaseExport, 'database_produk.xlsx')),
                    //->hidden(fn ($livewire) => $livewire->getOwnerRecord()->status !== 'Draft'),

                Tables\Actions\Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn () => Excel::download(new RfqItemTemplateExport, 'template_rfq_items.xlsx')),
                    //->hidden(fn ($livewire) => $livewire->getOwnerRecord()->status !== 'Draft'),

                Tables\Actions\Action::make('import')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->form([
                        Forms\Components\FileUpload::make('attachment')
                            ->label('File Excel (XLSX)')
                            ->disk('local') 
                            ->directory('imports')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv'])
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire) {
                        $rfqId = $livewire->getOwnerRecord()->id;
                        $filePath = Storage::disk('local')->path($data['attachment']);
                        if (!file_exists($filePath)) {
                            Notification::make()->title('File Gagal Ditemukan')->danger()->send();
                            return;
                        }
                        try {
                            Excel::import(new RfqItemsImport($rfqId), $filePath);
                            Storage::disk('local')->delete($data['attachment']);
                            Notification::make()->title('Import Berhasil')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal Memproses Excel')->body('Error: ' . $e->getMessage())->danger()->send();
                        }
                    }),
                    //->hidden(fn ($livewire) => in_array($livewire->getOwnerRecord()->status, ['Disetujui', 'Selesai'])),

                Tables\Actions\CreateAction::make()
                    ->label('Tambah Manual')
                    ->icon('heroicon-m-plus')
                    ->modalHeading('Tambah Barang RFQ')
                    ->createAnother(false),
                    //->hidden(fn ($livewire) => in_array($livewire->getOwnerRecord()->status, ['Disetujui', 'Selesai'])),
            ])
            ->actions([    
                Tables\Actions\EditAction::make()
                    ->label('Edit') // Opsional
                    ->modalHeading('Edit Item RFQ'), // Opsional          
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($livewire) => in_array($livewire->getOwnerRecord()->status, ['Disetujui', 'Selesai'])),
            ]);
    }
}