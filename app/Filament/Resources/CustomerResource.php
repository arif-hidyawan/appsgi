<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers; 
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Filament\Concerns\HasPermissionPrefix;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users'; 
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Customer';
    protected static ?string $modelLabel = 'Customer';
    protected static ?int $navigationSort = 2; 

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'customer';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // KIRI: Data Utama
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Identitas Perusahaan')
                            ->schema([
                                Forms\Components\TextInput::make('customer_code')
                                    ->label('Kode Customer')
                                    ->placeholder('AUTO / CUST-00X')
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50),

                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Perusahaan (PT/CV)')
                                    ->required()
                                    ->columnSpanFull()
                                    ->maxLength(255),

                                // --- HUT PERUSAHAAN ---
                                Forms\Components\DatePicker::make('anniversary_date')
                                    ->label('HUT Perusahaan')
                                    ->displayFormat('d F Y') 
                                    ->native(false) 
                                    ->columnSpanFull(),
                                // ----------------------

                                Forms\Components\TextInput::make('tax_id')
                                    ->label('NPWP / Tax ID')
                                    ->maxLength(50),

                               
                                
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Status Aktif')
                                    ->default(true)
                                    ->inline(false),
                            ])->columns(2),

                        Forms\Components\Section::make('Alamat')
                            ->schema([
                                Forms\Components\Textarea::make('billing_address')
                                    ->label('Alamat Tagihan (Kantor)')
                                    ->rows(2),
                                
                                Forms\Components\Textarea::make('shipping_address')
                                    ->label('Alamat Pengiriman (Gudang)')
                                    ->rows(2),
                            ]),
                    ])->columnSpan(2),

                // KANAN: Kontak & Finansial
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Kontak Umum')
                            ->schema([
                                Forms\Components\TextInput::make('phone')
                                    ->label('Telepon Kantor')
                                    ->tel(),
                                
                                Forms\Components\TextInput::make('email')
                                    ->label('Email Umum')
                                    ->email(),
                                
                                Forms\Components\TextInput::make('website')
                                    ->label('Website')
                                    ->prefix('https://'),
                            ]),

                        Forms\Components\Section::make('Ketentuan Bisnis')
                            ->schema([
                                Forms\Components\TextInput::make('payment_terms')
                                    ->label('Termin Pembayaran')
                                    ->placeholder('Contoh: Net 30 Days'),
                                
                                Forms\Components\TextInput::make('credit_limit')
                                    ->label('Limit Kredit (Rp)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0),
                            ]),
                    ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer_code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Customer $record) => $record->tax_id ? 'NPWP: ' . $record->tax_id : null),

                // --- INFO HUT DI TABEL ---
                Tables\Columns\TextColumn::make('anniversary_date')
                    ->label('HUT')
                    ->date('d M') 
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), 
                // -------------------------

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telp')
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('payment_terms')
                    ->label('Termin')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('credit_limit')
                    ->label('Limit Kredit')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
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
            RelationManagers\ContactsRelationManager::class,
            RelationManagers\SalesOrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}