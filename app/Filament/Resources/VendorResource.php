<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorResource\Pages;
use App\Filament\Resources\VendorResource\RelationManagers;
use App\Models\Vendor;
use App\Models\VendorContact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Filament\Concerns\HasPermissionPrefix;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Vendor';
    protected static ?string $modelLabel = 'Vendor';

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'vendor';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Vendor')
                            ->schema([
                                // Baris 1: Kode & Nama
                                Forms\Components\TextInput::make('vendor_code')
                                    ->label('Kode Vendor')
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('V-001')
                                    ->maxLength(50),

                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Perusahaan')
                                    ->required()
                                    ->maxLength(255),

                                // Baris 2: Kontak Utama (BARU DITAMBAHKAN)
                                Forms\Components\TextInput::make('phone')
                                    ->label('Telepon Kantor')
                                    ->tel()
                                    ->maxLength(50),

                                Forms\Components\TextInput::make('email')
                                    ->label('Email Kantor')
                                    ->email()
                                    ->maxLength(255),

                                // Baris 3: Tanggal & Alamat
                                Forms\Components\DatePicker::make('company_anniversary')
                                    ->label('HUT Perusahaan')
                                    ->placeholder('Pilih tanggal berdiri')
                                    ->native(false)
                                    ->displayFormat('d F Y'),
                                
                                Forms\Components\Textarea::make('address')
                                    ->label('Alamat Kantor')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])->columns(2),
                    ])->columnSpan(2),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Detail Kerjasama')
                            ->schema([
                                Forms\Components\TextInput::make('payment_terms')
                                    ->label('Termin Pembayaran')
                                    ->placeholder('Net 30'),
                                
                                Forms\Components\TextInput::make('vendor_response')
                                    ->label('Respon / Status'),
                            ]),
                    ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Eager load contacts untuk optimasi performa
            ->modifyQueryUsing(fn ($query) => $query->with('contacts'))
            ->columns([
                Tables\Columns\TextColumn::make('vendor_code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Vendor')
                    ->searchable()
                    ->weight('bold')
                    ->sortable(),

                // KOLOM KONTAK UTAMA (BARU)
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->icon('heroicon-m-phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false), // Default muncul

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->icon('heroicon-m-envelope')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true), // Default sembunyi

                // KOLOM KATEGORI GABUNGAN (VIRTUAL)
                Tables\Columns\TextColumn::make('all_categories')
                    ->label('Kategori')
                    ->badge()
                    ->separator(',')
                    ->limitList(2)
                    ->getStateUsing(function ($record) {
                        return $record->contacts
                            ->pluck('category') 
                            ->flatten()         
                            ->unique()          
                            ->filter()
                            ->values()
                            ->toArray();
                    }),

                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('payment_terms')
                    ->label('Termin')
                    ->badge()
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('vendor_response')
                    ->label('Respon')
                    ->limit(20)
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('contacts_count')
                    ->counts('contacts')
                    ->label('Jml PIC')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),
            ])
            ->filters([
                // 1. FILTER KATEGORI (Dari tabel contacts)
                Tables\Filters\SelectFilter::make('category')
                    ->label('Filter Kategori')
                    ->searchable()
                    ->options(function () {
                        $categories = VendorContact::query()
                            ->whereNotNull('category')
                            ->pluck('category')
                            ->flatten()
                            ->unique()
                            ->filter()
                            ->values();
                        return $categories->mapWithKeys(fn ($item) => [$item => $item])->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (! empty($data['value'])) {
                            $query->whereHas('contacts', function ($q) use ($data) {
                                $q->whereJsonContains('category', $data['value']);
                            });
                        }
                    }),

                // 2. FILTER RESPON
                Tables\Filters\SelectFilter::make('vendor_response')
                    ->label('Status Respon')
                    ->options(fn () => Vendor::query()
                        ->whereNotNull('vendor_response')
                        ->distinct()
                        ->pluck('vendor_response', 'vendor_response')
                        ->toArray()
                    ),

                // 3. FILTER TERMIN
                Tables\Filters\SelectFilter::make('payment_terms')
                    ->label('Termin Pembayaran')
                    ->options(fn () => Vendor::query()
                        ->whereNotNull('payment_terms')
                        ->distinct()
                        ->pluck('payment_terms', 'payment_terms')
                        ->toArray()
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ContactsRelationManager::class,
            RelationManagers\PurchaseOrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendors::route('/'),
            'create' => Pages\CreateVendor::route('/create'),
            'edit' => Pages\EditVendor::route('/{record}/edit'),
        ];
    }
}