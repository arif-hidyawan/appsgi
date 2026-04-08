<?php

namespace App\Filament\Resources\PurchaseOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Barang yang Dibeli';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- 1. PRODUK ---
                Forms\Components\Select::make('product_id')
                    ->label('Produk Master')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live() // Wajib live agar bisa trigger auto-fill
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $product = \App\Models\Product::find($state);
                            if ($product) {
                                // Auto-fill custom_name dengan nama asli produk
                                $set('custom_name', $product->name);
                            }
                        }
                    })
                    ->columnSpanFull()
                    ->disabled(fn () => $this->getOwnerRecord()->status !== 'Draft'),

                // --- 2. CUSTOM NAME (BARU) ---
                Forms\Components\TextInput::make('custom_name')
                    ->label('Nama Produk di PO (Sesuai Vendor)')
                    ->helperText('Edit nama ini jika ingin menyesuaikan dengan PO Vendor. Master Item tidak akan berubah.')
                    ->required() 
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->disabled(fn () => $this->getOwnerRecord()->status !== 'Draft'),

                // --- 3. NOTES ---
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan Item')
                    ->placeholder('Contoh: Spesifikasi khusus, warna, batch number, dll.')
                    ->rows(2)
                    ->columnSpanFull()
                    ->disabled(fn () => $this->getOwnerRecord()->status !== 'Draft'),

                // --- 4. INPUT ANGKA (GROUPING) ---
                Forms\Components\Group::make([
                    Forms\Components\TextInput::make('qty')
                        ->numeric()
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Forms\Set $set, $state, Forms\Get $get) => 
                            $set('subtotal', (int)$state * (float)$get('unit_price'))
                        )
                        ->disabled(fn () => $this->getOwnerRecord()->status !== 'Draft'),

                    Forms\Components\TextInput::make('lead_time')
                        ->label('Est. Inden')
                        ->numeric()
                        ->default(0)
                        ->suffix('Hari')
                        ->datalist([0, 3, 5, 7, 14, 30, 45, 60, 90])
                        ->placeholder('0 = Ready')
                        ->required()
                        ->disabled(fn () => $this->getOwnerRecord()->status !== 'Draft'),

                    Forms\Components\TextInput::make('unit_price')
                        ->label('Harga Beli (HPP)')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Forms\Set $set, $state, Forms\Get $get) => 
                            $set('subtotal', (int)$get('qty') * (float)$state)
                        )
                        ->disabled(fn () => $this->getOwnerRecord()->status !== 'Draft'),

                    Forms\Components\TextInput::make('subtotal')
                        ->numeric()
                        ->prefix('Rp')
                        ->readOnly(),
                ])->columns(4)->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('custom_name') // Ubah title attribute ke custom name
            ->columns([
                Tables\Columns\ImageColumn::make('product.image')
                    ->label('Foto')
                    ->circular(),

                // Menampilkan Custom Name sebagai nama utama di tabel
                Tables\Columns\TextColumn::make('custom_name')
                    ->label('Produk (Di PO)')
                    ->description(function ($record) {
                        // Tampilkan nama aslinya di bawahnya sebagai referensi
                        $text = "Master: " . ($record->product ? $record->product->name : '-');
                        $text .= " | Code: " . ($record->product ? $record->product->item_code : '-');
                        
                        if ($record->notes) {
                            $text .= " | Note: " . $record->notes;
                        }
                        return $text;
                    })
                    ->wrap()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty')
                    ->alignCenter(),

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
                
                Tables\Columns\TextColumn::make('unit_price')
                    ->money('IDR')
                    ->label('Harga Beli'),
                
                Tables\Columns\TextColumn::make('subtotal')
                    ->money('IDR')
                    ->label('Subtotal')
                    ->weight('bold'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Ubah')
                    ->modalHeading('Ubah Detail Barang')
                    ->visible(fn () => $this->getOwnerRecord()->status === 'Draft')
                    ->after(fn ($livewire) => self::updateGrandTotal($livewire->getOwnerRecord())),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->status === 'Draft')
                    ->after(fn ($livewire) => self::updateGrandTotal($livewire->getOwnerRecord())),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => $this->getOwnerRecord()->status === 'Draft')
                        ->after(fn ($livewire) => self::updateGrandTotal($livewire->getOwnerRecord())),
                ]),
            ]);
    }

    public static function updateGrandTotal(Model $record): void
    {
        if ($record) {
            $total = $record->items()->sum('subtotal');
            $record->update(['grand_total' => $total]);
        }
    }
}