<?php

namespace App\Filament\Resources\QuotationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\Summarizers\Sum; 

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Detail Item Penawaran';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- BAGIAN 1: PRODUK & VENDOR ---
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Produk')
                            ->relationship('product', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->item_code} - {$record->name}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1)
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                // Reset harga saat produk ganti
                                $set('unit_price', 0);
                                $set('cost_price', 0);
                                $set('profit_percentage', 0);
                            }),

                        Forms\Components\Select::make('vendor_id')
                            ->label('Suplier / Vendor')
                            ->relationship('vendor', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih Vendor (Opsional)')
                            ->columnSpan(1),
                    ])->columns(2)->columnSpanFull(),

                // --- BAGIAN 2: CATATAN ---
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan / Spesifikasi')
                    ->placeholder('Contoh: Warna Merah, Garansi 1 Tahun')
                    ->rows(2)
                    ->columnSpanFull(),

                // --- BAGIAN 3: HARGA & KALKULASI ---
                Forms\Components\Section::make('Kalkulasi Harga')
                    ->compact()
                    ->schema([
                        Forms\Components\Grid::make(3) // Ubah Grid jadi 3 agar tampilan rapi
                            ->schema([
                                Forms\Components\TextInput::make('qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Forms\Set $set, $state, Forms\Get $get) => 
                                        $set('subtotal', (int)$state * (float)$get('unit_price'))
                                    ),

                                // --- TAMBAHAN: INPUT INDEN ---
                                Forms\Components\TextInput::make('lead_time')
                                    ->label('Est. Inden')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Hari')
                                    ->datalist([0, 3, 5, 7, 14, 30, 45, 60, 90])
                                    ->placeholder('0 = Ready')
                                    ->required(),
                                // -----------------------------

                                // 1. INPUT HPP (COST PRICE)
                                Forms\Components\TextInput::make('cost_price')
                                    ->label('HPP (Modal)')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        $margin = (float) $get('profit_percentage');
                                        $hpp = (float) $state;

                                        if ($margin > 0 && $margin < 100) {
                                            $sellingPrice = $hpp / (1 - ($margin / 100));
                                            $set('unit_price', round($sellingPrice));
                                            
                                            $qty = (int) $get('qty');
                                            $set('subtotal', $qty * round($sellingPrice));
                                        }
                                    }),

                                // 2. INPUT MANUAL LABA (%)
                                Forms\Components\TextInput::make('profit_percentage')
                                    ->label('Laba (%)')
                                    ->helperText('Target Margin')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(0)
                                    ->live(onBlur: true) 
                                    ->dehydrated(false) 
                                    ->formatStateUsing(function ($record) {
                                        if (!$record || $record->cost_price <= 0 || $record->unit_price <= 0) return 0;
                                        $hpp = (float) $record->cost_price;
                                        $selling = (float) $record->unit_price;
                                        return round((($selling - $hpp) / $selling) * 100, 2);
                                    })
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        $hpp = (float) $get('cost_price');
                                        $margin = (float) $state;

                                        if ($hpp > 0 && $margin < 100) {
                                            $sellingPrice = $hpp / (1 - ($margin / 100));
                                            $set('unit_price', round($sellingPrice));

                                            $qty = (int) $get('qty');
                                            $set('subtotal', $qty * round($sellingPrice));
                                        }
                                    }),

                                // 3. HARGA JUAL (UNIT PRICE)
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Harga Jual')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        $hpp = (float) $get('cost_price');
                                        $selling = (float) $state;
                                        $qty = (int) $get('qty');

                                        $set('subtotal', $qty * $selling);

                                        if ($selling > 0 && $hpp > 0) {
                                            $margin = (($selling - $hpp) / $selling) * 100;
                                            $set('profit_percentage', round($margin, 2));
                                        }
                                    }),

                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->readOnly(),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
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
                        $text = "Code: " . ($record->product->item_code ?? '-');
                        
                        if ($record->vendor) {
                            $text .= " | Vendor: " . $record->vendor->name;
                        }

                        if ($record->notes) {
                            $text .= " | Note: " . $record->notes;
                        }
                        
                        return $text;
                    })
                    ->wrap()
                    ->sortable(),

                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty')
                    ->alignCenter(),

                // --- TAMBAHAN: KOLOM INDEN ---
                Tables\Columns\TextColumn::make('lead_time')
                    ->label('Inden')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state == 0 ? 'Ready' : $state . ' Hari')
                    ->color(fn ($state) => match (true) {
                        $state == 0 => 'success',
                        $state <= 7 => 'info',
                        $state <= 30 => 'warning',
                        default => 'danger',
                    })
                    ->alignCenter()
                    ->sortable(),
                // -----------------------------

                // Kolom HPP (Hidden by default)
                Tables\Columns\TextColumn::make('cost_price')
                    ->label('HPP')
                    ->money('IDR')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Harga Satuan')
                    ->money('IDR'),

                // Kolom Laba (%) di Tabel
                Tables\Columns\TextColumn::make('profit_margin')
                    ->label('Laba (%)')
                    ->state(function ($record) {
                        $hpp = (float) $record->cost_price;
                        $jual = (float) $record->unit_price;

                        if ($jual <= 0) return '-';

                        // Rumus Gross Margin
                        $persen = (($jual - $hpp) / $jual) * 100;
                        return number_format($persen, 1) . '%';
                    })
                    ->badge()
                    ->color(fn ($state) => str_contains($state, '-') ? 'danger' : 'success'),

                // --- SUBTOTAL & TOTAL SUMMARY ---
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR')
                    ->weight('bold')
                    ->color('primary')
                    ->summarize(
                        Sum::make()
                            ->label('Total Penawaran')
                            ->money('IDR')
                    ),
            ])
            ->headerActions([
                 Tables\Actions\CreateAction::make()
                    ->after(fn ($livewire) => self::updateGrandTotal($livewire->getOwnerRecord())),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(fn ($livewire) => self::updateGrandTotal($livewire->getOwnerRecord())),
                    //->hidden(fn ($livewire) => !in_array($livewire->getOwnerRecord()->status, ['Draft', 'Sent', 'Partial'])),

                Tables\Actions\DeleteAction::make()
                    ->after(fn ($livewire) => self::updateGrandTotal($livewire->getOwnerRecord())),
                    //->hidden(fn ($livewire) => !in_array($livewire->getOwnerRecord()->status, ['Draft', 'Sent', 'Partial'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(fn ($livewire) => self::updateGrandTotal($livewire->getOwnerRecord())),
                        //->hidden(fn ($livewire) => !in_array($livewire->getOwnerRecord()->status, ['Draft', 'Sent', 'Partial'])),
                ]),
            ]);
    
    }

    public static function updateGrandTotal(Model $quotation): void
    {
        $total = $quotation->items()->sum('subtotal');
        $quotation->update(['grand_total' => $total]);
    }
}