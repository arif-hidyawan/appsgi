<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder; 
use Illuminate\Support\Str; 

use App\Filament\Concerns\HasPermissionPrefix;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Item / Produk';
    protected static ?string $modelLabel = 'Produk';
    protected static ?int $navigationSort = 3;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'product';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Barang')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Produk')
                                    ->required()
                                    ->columnSpanFull()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('item_code')
                                    ->label('Kode Item')
                                    ->default(function () {
                                        $lastProduct = \App\Models\Product::orderBy('id', 'desc')->first();
                                        $lastNumber = $lastProduct ? (int) substr($lastProduct->item_code, 4) : 0;
                                        return 'ITM-' . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
                                    })
                                    ->readOnly() 
                                    ->required()
                                    ->maxLength(50),

                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU (Pabrik)')
                                    ->placeholder('Opsional')
                                    ->maxLength(50),

                                Forms\Components\TextInput::make('barcode')
                                    ->label('Barcode / UPC')
                                    ->maxLength(50),

                                Forms\Components\Select::make('brand_id')
                                    ->label('Merek / Brand')
                                    ->relationship('brand', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nama Merek')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Forms\Set $set, $state) => 
                                                $set('slug', Str::slug($state))
                                            ),
                                        Forms\Components\TextInput::make('slug')
                                            ->disabled()
                                            ->dehydrated()
                                            ->required()
                                            ->unique('brands', 'slug'),
                                        Forms\Components\Hidden::make('is_active')->default(true),
                                    ]),

                                Forms\Components\Select::make('vendor_id')
                                    ->label('Supplier / Vendor Default')
                                    ->relationship('vendor', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nama Vendor')
                                            ->required(),
                                    ]),

                                Forms\Components\Select::make('category_id')
                                    ->label('Kategori')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nama Kategori')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Forms\Set $set, $state) => 
                                                $set('slug', Str::slug($state))
                                            ),
                                        Forms\Components\TextInput::make('slug')
                                            ->disabled()
                                            ->dehydrated()
                                            ->required()
                                            ->unique('categories', 'slug'),
                                        Forms\Components\Hidden::make('is_active')->default(true),
                                    ]),

                                Forms\Components\Select::make('unit_id')
                                    ->label('Satuan')
                                    ->relationship('unit', 'name')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->code})")
                                    ->searchable(['name', 'code'])
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nama Satuan')
                                            ->placeholder('Pieces')
                                            ->required(),
                                        Forms\Components\TextInput::make('code')
                                            ->label('Kode')
                                            ->placeholder('PCS')
                                            ->required()
                                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                                        Forms\Components\Hidden::make('is_active')->default(true),
                                    ]),
                                    
                                Forms\Components\TextInput::make('weight_gram')
                                    ->label('Berat (Gram)')
                                    ->numeric()
                                    ->step(100),
                            ])->columns(2),

                        Forms\Components\Section::make('Gambar & Deskripsi')
                            ->schema([
                                Forms\Components\FileUpload::make('image')
                                    ->label('Foto Produk')
                                    ->image()
                                    ->directory('products')
                                    ->maxSize(2048),

                                Forms\Components\RichEditor::make('description')
                                    ->label('Deskripsi Lengkap')
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(2),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Stok & Status')
                            ->schema([
                                Forms\Components\Placeholder::make('total_stock')
                                    ->label('Total Stok Fisik (Real-time)')
                                    ->content(function (?Product $record) {
                                        if (!$record) return 0;
                                        return number_format($record->stocks()->sum('quantity'), 0, ',', '.');
                                    }),

                                // --- [NEW] KLASIFIKASI STOK ---
                                Forms\Components\Select::make('stock_classification_id')
                                    ->label('Klasifikasi Stok')
                                    ->relationship('stockClassification', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Pilih Klasifikasi')
                                    ->helperText('Cth: Fast Moving, Dead Stock')
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->required()->label('Nama Klasifikasi'),
                                        Forms\Components\Select::make('color')
                                            ->options(['success'=>'Hijau','warning'=>'Kuning','danger'=>'Merah','gray'=>'Abu-abu'])
                                            ->default('gray')
                                            ->required(),
                                        Forms\Components\Hidden::make('slug')->default(fn()=>Str::random(5)), // Dummy logic, better handle in logic
                                    ])
                                    ->createOptionUsing(function ($data) {
                                        $data['slug'] = Str::slug($data['name']);
                                        return \App\Models\StockClassification::create($data)->getKey();
                                    }),
                                // ------------------------------

                                Forms\Components\TextInput::make('min_stock')
                                    ->label('Minimum Stok')
                                    ->helperText('Alert jika stok di bawah ini')
                                    ->numeric()
                                    ->default(5),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Produk Aktif')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger'),
                            ]),
                    ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Foto')
                    ->circular(),

                Tables\Columns\TextColumn::make('item_code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Item')
                    ->searchable()
                    ->description(fn (Product $record) => $record->sku ? "SKU: {$record->sku}" : null)
                    ->wrap()
                    ->weight('bold'),

                // --- [NEW] KOLOM KLASIFIKASI STOK ---
                Tables\Columns\TextColumn::make('stockClassification.name')
                    ->label('Klasifikasi')
                    ->badge()
                    // Mengambil warna dari database yang sudah kita setup
                    ->color(fn ($record) => $record->stockClassification->color ?? 'gray') 
                    ->sortable()
                    ->toggleable(),
                // ------------------------------------

                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Merek')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_sum')
                    ->label('Total Stok')
                    ->state(fn (Product $record) => $record->stocks()->sum('quantity'))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->withSum('stocks', 'quantity')->orderBy('stocks_sum_quantity', $direction);
                    })
                    ->color(fn ($state, Product $record) => $state <= $record->min_stock ? 'danger' : 'success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('unit.code')
                    ->label('Satuan')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                // --- [NEW] FILTER KLASIFIKASI ---
                Tables\Filters\SelectFilter::make('stock_classification_id')
                    ->label('Filter Klasifikasi')
                    ->relationship('stockClassification', 'name')
                    ->preload(),
                // --------------------------------

                Tables\Filters\SelectFilter::make('vendor_id')
                    ->label('Filter Vendor')
                    ->relationship('vendor', 'name'),

                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Filter Kategori')
                    ->relationship('category', 'name'),

                Tables\Filters\SelectFilter::make('brand_id')
                    ->label('Filter Merek')
                    ->relationship('brand', 'name'),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            RelationManagers\StocksRelationManager::class,
            RelationManagers\PurchaseHistoryRelationManager::class,
            RelationManagers\SalesHistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}