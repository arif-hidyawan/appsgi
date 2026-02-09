<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Support\HtmlString;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Chart of Accounts';
    protected static ?string $navigationGroup = 'Accounting';
    protected static ?int $navigationSort = 15;

    // Supaya otomatis ter-scope ke Tenant/Perusahaan aktif
    protected static bool $isScopedToTenant = true; 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Akun')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Akun')
                            ->required()
                            // Update Validasi: Unik berdasarkan Company ID
                            ->unique(
                                ignoreRecord: true, 
                                modifyRuleUsing: function (Unique $rule) {
                                    // Cek unik hanya di perusahaan yang sedang login
                                    return $rule->where('company_id', filament()->getTenant()->id);
                                }
                            ),
                        
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Akun')
                            ->required(),

                        Forms\Components\Select::make('parent_id')
                            ->label('Induk Akun (Parent)')
                            // Filter: Hanya tampilkan Header dari perusahaan yang sama
                            ->relationship('parent', 'name', function (Builder $query) {
                                return $query->where('type', 'H')
                                             ->where('company_id', filament()->getTenant()->id);
                            })
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('type')
                            ->label('Tipe')
                            ->options([
                                'H' => 'Header (Induk)',
                                'D' => 'Detail (Sub)',
                            ])
                            ->default('D')
                            ->required(),

                        Forms\Components\Select::make('nature')
                            ->label('Sifat Akun')
                            ->options([
                                'asset' => 'Asset (Harta)',
                                'liability' => 'Liability (Kewajiban)',
                                'equity' => 'Equity (Modal)',
                                'revenue' => 'Revenue (Pendapatan)',
                                'expense' => 'Expense (Beban)',
                                'cogs' => 'COGS (HPP)',
                                'other_revenue' => 'Pendapatan Lain',
                                'other_expense' => 'Biaya Lain',
                            ])
                            ->required(),
                    ]),

                Forms\Components\Section::make('Konfigurasi')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Toggle::make('is_cash_bank')
                            ->label('Kas/Bank'),
                        Forms\Components\Toggle::make('is_inventory')
                            ->label('Akun Persediaan'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            // 1. KOLOM PERUSAHAAN (BARU)
            Tables\Columns\TextColumn::make('company.name')
                ->label('Perusahaan')
                ->sortable()
                ->searchable()
                ->badge() // Biar tampilannya lebih cantik kayak label
                ->color('gray'),

            Tables\Columns\TextColumn::make('code')
                ->label('Kode')
                ->sortable()
                ->searchable()
                ->weight('bold'),

            // Tree View Visual
            Tables\Columns\TextColumn::make('name')
                ->label('Nama Akun')
                ->searchable()
                ->formatStateUsing(function (Account $record) {
                    $prefix = $record->parent_id ? '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;↳ ' : '';
                    $name = $record->type === 'H' ? "<strong>{$record->name}</strong>" : $record->name;
                    return new HtmlString($prefix . $name);
                })
                ->html(),

            Tables\Columns\TextColumn::make('type')
                ->badge()
                ->colors([
                    'warning' => 'H',
                    'success' => 'D',
                ]),

            Tables\Columns\TextColumn::make('nature')
                ->label('Nature')
                ->badge(),

            Tables\Columns\IconColumn::make('is_active')
                ->boolean(),
        ])
        ->defaultSort('code') // Default urut kode
        
        // 2. GROUPING (Opsional: Mengelompokkan baris per Perusahaan)
        // ->groups([
        //     Tables\Grouping\Group::make('company.name')
        //         ->label('Perusahaan')
        //         ->collapsible(),
        // ])
        
        ->filters([
            // 3. FILTER PERUSAHAAN (BARU)
            Tables\Filters\SelectFilter::make('company_id')
                ->label('Filter Perusahaan')
                ->relationship('company', 'name') // Ambil list dari tabel companies
                ->searchable()
                ->preload(),

            Tables\Filters\SelectFilter::make('type')
                ->options([
                    'H' => 'Header',
                    'D' => 'Detail',
                ]),
                
            Tables\Filters\SelectFilter::make('nature')
                ->options([
                    'asset' => 'Asset',
                    'liability' => 'Liability',
                    'equity' => 'Equity',
                    'revenue' => 'Revenue',
                    'expense' => 'Expense',
                ]),
        ]);
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}