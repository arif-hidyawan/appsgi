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
use Filament\Facades\Filament;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Chart of Accounts';
    protected static ?string $navigationGroup = 'Accounting';
    protected static ?int $navigationSort = 15;

    // Matikan strict scoping jika ingin bebas sebagai Super Admin
    protected static bool $isScopedToTenant = false; 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Akun')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Perusahaan')
                            ->relationship('company', 'name')
                            ->default(fn () => Filament::getTenant()?->id)
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(), // Penting agar data lain bisa refresh jika perusahaan diganti
                        
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Akun')
                            ->required()
                            ->unique(
                                ignoreRecord: true, 
                                modifyRuleUsing: function (Unique $rule, Forms\Get $get) {
                                    // Unique dicek berdasarkan perusahaan yang dipilih di atas
                                    return $rule->where('company_id', $get('company_id'));
                                }
                            ),
                        
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Akun')
                            ->required(),

                        Forms\Components\Select::make('parent_id')
                            ->label('Induk Akun (Parent)')
                            ->relationship(
                                name: 'parent', 
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query, Forms\Get $get) {
                                    // Bebaskan query: Super Admin bisa lihat semua Header
                                    // Atau jika ingin sedikit rapi, filter berdasarkan PT yang dipilih saja
                                    $companyId = $get('company_id');
                                    return $query->where('type', 'H')
                                        ->when($companyId, fn($q) => $q->where('company_id', $companyId));
                                }
                            )
                            ->getOptionLabelFromRecordUsing(fn (Account $record) => "{$record->code} - {$record->name}")
                            ->searchable(['code', 'name']) 
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
                            ->required(), // Pastikan ini tidak ter-hidden atau disabled
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
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['parent', 'parent.parent', 'parent.parent.parent', 'parent.parent.parent.parent']))
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Akun')
                    ->searchable()
                    ->formatStateUsing(function (Account $record) {
                        $depth = 0;
                        $parent = $record->parent;
                        while ($parent) {
                            $depth++;
                            $parent = $parent->parent;
                        }

                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
                        $prefix = $depth > 0 ? $indent . '↳ ' : '';
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
            ->defaultSort('code')
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name'),
                Tables\Filters\SelectFilter::make('type')
                    ->options(['H' => 'Header', 'D' => 'Detail']),
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