<?php

namespace App\Filament\Resources\SalesOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\ProductStock;
use App\Models\SalesOrderItem;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth; 
use Filament\Tables\Columns\Summarizers\Sum;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Barang Pesanan';

    public function reserveStock($itemId, $warehouseId)
    {
        $item = SalesOrderItem::find($itemId);
        if (!$item) return;

        // 1. Ambil Stok Fisik
        $physicalStock = ProductStock::where('product_id', $item->product_id)
            ->where('warehouse_id', $warehouseId)
            ->sum('quantity');

        // 2. Hitung Stok yang Sedang Dikunci (Reserved)
        $reservedStock = SalesOrderItem::where('product_id', $item->product_id)
            ->where('warehouse_id', $warehouseId)
            ->whereNotNull('reserved_at')
            ->where('id', '!=', $itemId) 
            ->whereHas('salesOrder', function($q) {
                $q->whereNotIn('status', ['Completed', 'Cancelled']);
            })
            ->sum('qty');

        // 3. Hitung Stok Bebas
        $availableStock = $physicalStock - $reservedStock;

        // 4. Cek Validasi
        if ($availableStock < $item->qty) {
            Notification::make()
                ->title('Gagal Mengunci')
                ->body("Stok fisik ada ({$physicalStock}), tapi stok bebas tidak cukup (Sisa: {$availableStock}).")
                ->danger()
                ->send();
            return;
        }

        $item->update([
            'warehouse_id' => $warehouseId,
            'reserved_at' => now(),
        ]);

        Notification::make()->title('Stok Terkunci')->success()->send();
        $this->dispatch('close-modal', id: 'check-holding-modal'); 
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- BAGIAN 1: PRODUK & VENDOR ---
                Forms\Components\Group::make([
                    Forms\Components\Select::make('product_id')
    ->label('Produk')
    ->relationship('product', 'name')
    ->searchable()->preload()->required()
    ->live()
    ->afterStateUpdated(function (Forms\Set $set, $state) {
        // Reset harga & lain-lain
        $set('unit_price', 0);
        $set('cost_price', 0);
        $set('profit_percentage', 0);
        
        if ($state) {
            $product = \App\Models\Product::find($state);
            // OTOMATIS ISI NAMA CUSTOM DENGAN NAMA MASTER SAAT PERTAMA PILIH
            $set('custom_name', $product->name); 
        }
    })
    ->disabled(fn () => $this->getOwnerRecord()->status !== 'New'),

// --- KOLOM NAMA CUSTOM (YANG BISA DIEDIT) ---
Forms\Components\TextInput::make('custom_name')
    ->label('Nama Produk di SO')
    ->helperText('Edit nama ini jika ingin menyesuaikan dengan PO Customer. Master Item tidak akan berubah.')
    ->required() // Wajib ada (minimal copy dari master)
    ->maxLength(255)
    ->disabled(fn () => $this->getOwnerRecord()->status !== 'New'),

                    Forms\Components\Select::make('vendor_id')
                        ->label('Vendor Sumber')
                        ->relationship('vendor', 'name')
                        ->searchable()->preload()
                        ->disabled(fn () => $this->getOwnerRecord()->status !== 'New'),
                ])->columns(2)->columnSpanFull(),

                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(2)->columnSpanFull()
                    ->disabled(fn () => $this->getOwnerRecord()->status !== 'New'),

                // --- BAGIAN 2: KALKULASI HARGA & LABA ---
                Forms\Components\Section::make('Kalkulasi Harga & Laba')
                    ->compact()
                    ->schema([
                        Forms\Components\Grid::make(4)->schema([
                            
                            Forms\Components\TextInput::make('qty')
                                ->label('Qty')
                                ->numeric()->required()->live(onBlur: true)
                                ->afterStateUpdated(fn (Forms\Set $set, $state, Forms\Get $get) => 
                                    $set('subtotal', (int)$state * (float)$get('unit_price'))
                                )
                                ->disabled(fn () => $this->getOwnerRecord()->status !== 'New'),

                            // 1. INPUT HPP (COST PRICE)
                            Forms\Components\TextInput::make('cost_price')
                                ->label('HPP (Modal)')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->live(onBlur: true) 
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                    // HPP Berubah -> Hitung ulang Jual berdasarkan Margin yg ada
                                    $margin = (float) $get('profit_percentage');
                                    $hpp = (float) $state;

                                    if ($margin > 0 && $margin < 100) {
                                        // Rumus Gross Margin: Selling = HPP / (1 - Margin%)
                                        $sellingPrice = $hpp / (1 - ($margin / 100));
                                        $set('unit_price', round($sellingPrice));
                                        
                                        // Update Subtotal
                                        $set('subtotal', (int)$get('qty') * round($sellingPrice));
                                    }
                                })
                                ->disabled(fn () => $this->getOwnerRecord()->status !== 'New'),

                            // 2. INPUT MANUAL LABA (%) - DENGAN AFTER STATE HYDRATED
                            Forms\Components\TextInput::make('profit_percentage')
                                ->label('Laba (%)')
                                ->helperText('Target Margin')
                                ->numeric()
                                ->suffix('%')
                                ->default(0)
                                ->live(onBlur: true)
                                ->dehydrated(false) // Tetap pasang ini untuk jaga-jaga
                                ->afterStateHydrated(function (Forms\Components\TextInput $component, $record) {
                                    if (!$record) return;

                                    $hpp = (float) $record->cost_price;
                                    $jual = (float) $record->unit_price;

                                    if ($jual > 0) {
                                        $margin = (($jual - $hpp) / $jual) * 100;
                                        $component->state(round($margin, 2));
                                    } else {
                                        $component->state(0);
                                    }
                                })
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                    // Input Laba -> Hitung Harga Jual
                                    $hpp = (float) $get('cost_price');
                                    $margin = (float) $state;

                                    if ($hpp > 0 && $margin < 100) {
                                        $sellingPrice = $hpp / (1 - ($margin / 100));
                                        $set('unit_price', round($sellingPrice));
                                        $set('subtotal', (int)$get('qty') * round($sellingPrice));
                                    }
                                })
                                ->disabled(fn () => $this->getOwnerRecord()->status !== 'New'),

                            // 3. INPUT HARGA JUAL
                            Forms\Components\TextInput::make('unit_price')
                                ->label('Harga Jual')
                                ->numeric()
                                ->prefix('Rp')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                    // Harga Jual Berubah -> Hitung Subtotal & Hitung Balik Margin %
                                    $hpp = (float) $get('cost_price');
                                    $selling = (float) $state;
                                    $qty = (int) $get('qty');

                                    // Update Subtotal
                                    $set('subtotal', $qty * $selling);

                                    // Hitung Margin Balik
                                    if ($selling > 0 && $hpp > 0) {
                                        $margin = (($selling - $hpp) / $selling) * 100;
                                        $set('profit_percentage', round($margin, 2));
                                    }
                                })
                                ->disabled(fn () => $this->getOwnerRecord()->status !== 'New'),

                            Forms\Components\TextInput::make('subtotal')
                                ->label('Subtotal')
                                ->numeric()
                                ->prefix('Rp')
                                ->readOnly(),
                        ]),

                        // 4. INDIKATOR LABA (TEXT)
                        Forms\Components\Placeholder::make('profit_indicator')
                            ->label('Estimasi Laba (Gross Margin)')
                            ->content(function (Forms\Get $get) {
                                $hpp = (float) $get('cost_price');
                                $jual = (float) $get('unit_price');
                                
                                if ($jual <= 0) return '-';

                                $profitRp = $jual - $hpp;
                                $margin = ($profitRp / $jual) * 100;

                                $color = $profitRp > 0 ? 'text-success-600' : 'text-danger-600';
                                return new \Illuminate\Support\HtmlString(
                                    "<span class='font-bold {$color}'>" . number_format($margin, 1) . "% (Rp " . number_format($profitRp, 0, ',', '.') . ")</span>"
                                );
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                Tables\Columns\ImageColumn::make('product.image')->circular(),

                Tables\Columns\TextColumn::make('product.name') // Label header tetap "Produk"
    ->label('Produk')
    ->formatStateUsing(function ($record) {
        // LOGIC TAMPILAN:
        // Jika ada custom_name, pakai itu. Jika tidak, pakai nama master.
        $displayName = $record->custom_name ?? $record->product->name;
        
        return $displayName;
    })
    ->description(function ($record) {
        // Tampilkan Kode Item Master di deskripsi agar orang gudang tetap tau barang aslinya apa
        $desc = "Master Code: " . $record->product->item_code;
        
        // Jika namanya beda, kasih info nama aslinya
        if ($record->custom_name && $record->custom_name !== $record->product->name) {
             $desc .= " | Orig: " . $record->product->name;
        }

        if ($record->notes) $desc .= " | Note: " . $record->notes;
        
        // ... logic reserved stock ...
        
        return $desc;
    })
    ->wrap()->sortable(),
                
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

                Tables\Columns\TextColumn::make('vendor.name') 
                    ->label('Vendor')
                    ->icon('heroicon-m-building-storefront')
                    ->placeholder('-')
                    ->color('gray')
                    ->toggleable()->sortable(),

                Tables\Columns\TextColumn::make('internal_stock')
                    ->label('Stok Bebas')
                    ->alignCenter()
                    ->state(function ($record, $livewire) {
                        $physicalStock = ProductStock::where('product_id', $record->product_id)
                            ->where('company_id', $livewire->getOwnerRecord()->company_id)
                            ->sum('quantity');

                        $totalReserved = SalesOrderItem::where('product_id', $record->product_id)
                            ->whereNotNull('reserved_at')
                            ->whereHas('salesOrder', function($q) use ($livewire) {
                                $q->where('company_id', $livewire->getOwnerRecord()->company_id)
                                  ->whereNotIn('status', ['Completed', 'Cancelled']);
                            })
                            ->sum('qty');

                        return max(0, $physicalStock - $totalReserved);
                    })
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger') 
                    ->icon(fn ($record) => $record->reserved_at ? 'heroicon-m-lock-closed' : 'heroicon-m-magnifying-glass-circle')
                    ->action(
                        Tables\Actions\Action::make('check_holding')
                            ->label(fn ($record) => $record->reserved_at ? 'Lihat Posisi' : 'Cek & Kunci')
                            ->icon(fn ($record) => $record->reserved_at ? 'heroicon-m-eye' : 'heroicon-m-key')
                            ->color(fn ($record) => $record->reserved_at ? 'info' : 'warning')
                            ->modalHeading(fn ($record) => 'Stok Global: ' . $record->product->name)
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false) 
                            ->modalCancelActionLabel('Tutup')
                            ->extraAttributes(['id' => 'check-holding-modal'])
                            ->modalContent(function ($record, $livewire) {
                                $myCompanyId = $livewire->getOwnerRecord()->company_id;
                                $allStocks = \App\Models\ProductStock::where('product_id', $record->product_id)
                                    ->where('quantity', '>', 0)
                                    ->with(['company', 'warehouse'])->get();
                                return view('filament.components.stock-holding-list', [
                                    'stocks' => $allStocks,
                                    'currentCompanyId' => $myCompanyId,
                                    'product' => $record->product,
                                    'orderItem' => $record, 
                                ]);
                            })
                    ),

                Tables\Columns\TextColumn::make('qty')->label('Qty Order')->alignCenter()->weight('bold'),
                
                Tables\Columns\TextColumn::make('cost_price')
                    ->label('HPP')
                    ->money('IDR')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('unit_price')->money('IDR')->label('Harga Jual'),
                
                Tables\Columns\TextColumn::make('profit_margin')
                    ->label('Laba')
                    ->state(function ($record) {
                        $hpp = (float) $record->cost_price;
                        $jual = (float) $record->unit_price;

                        if ($jual <= 0) return '-';
                        
                        $margin = (($jual - $hpp) / $jual) * 100;
                        return number_format($margin, 1) . '%';
                    })
                    ->badge()
                    ->color(fn ($state) => str_contains($state, '-') ? 'danger' : 'success')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR')
                    ->weight('bold')
                    ->summarize(
                        Sum::make()
                            ->label('Total Sales Order')
                            ->money('IDR')
                    ),
            ])
            // =========================================================
            // ACTION & HEADER ACTION DENGAN INTERCEPTOR (MUTATE)
            // =========================================================
            ->headerActions([
                 Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        unset($data['profit_percentage']); // <--- CEGAH DATA INI MASUK KE DB
                        return $data;
                    })
                    ->after(fn ($livewire) => self::updateGrandTotal($livewire->getOwnerRecord())),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        unset($data['profit_percentage']); // <--- CEGAH DATA INI MASUK KE DB
                        return $data;
                    })
                    ->visible(fn ($record) => $this->getOwnerRecord()->status === 'New' && $record->reserved_at === null)
                    ->after(fn ($livewire) => self::updateGrandTotal($livewire->getOwnerRecord())),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $this->getOwnerRecord()->status === 'New' && $record->reserved_at === null)
                    ->after(fn ($livewire) => self::updateGrandTotal($livewire->getOwnerRecord())),

                Tables\Actions\Action::make('unlock_stock')
                    ->label('Lepas Kunci')
                    ->icon('heroicon-m-lock-open')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => 
                        in_array($this->getOwnerRecord()->status, ['New', 'Processed']) 
                        && $record->reserved_at !== null
                    )
                    ->action(function ($record) {
                        $record->update(['warehouse_id' => null, 'reserved_at' => null]);
                        Notification::make()->title('Stok Dilepas (Unlock Success)')->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => $this->getOwnerRecord()->status === 'New')
                        ->after(fn ($livewire) => self::updateGrandTotal($livewire->getOwnerRecord())),
                ]),
            ]);
    }

    public static function updateGrandTotal(Model $record): void
{
    if (!$record) return;

    $record->refresh();
    // Load relasi tax dari SALES ORDER, bukan dari Customer lagi
    $record->load(['items', 'tax']); 

    $subtotal = $record->items->sum('subtotal');

    // Ambil rate dari Tax yang dipilih di SO
    $taxRate = $record->tax->rate ?? 0; // Mengambil dari relasi salesOrder->tax

    $taxAmount = 0;
    if ($taxRate > 0) {
        $taxAmount = $subtotal * ($taxRate / 100);
    }

    $grandTotal = $subtotal + $taxAmount;

    $record->update([
        'subtotal_amount' => $subtotal,
        'tax_amount'      => $taxAmount,
        'grand_total'     => $grandTotal
    ]);
}
}