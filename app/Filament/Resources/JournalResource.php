<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalResource\Pages;
use App\Models\Journal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\JournalResource\RelationManagers\LinesRelationManager;

class JournalResource extends Resource
{
    protected static ?string $model = Journal::class;
    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationLabel = 'Journal Entries';
    protected static ?string $navigationGroup = 'Accounting';
    protected static ?int $navigationSort = 16;

    /**
     * TOTALITAS: Filter query agar user hanya melihat jurnal dari perusahaan 
     * yang terdaftar di tabel pivot company_user mereka.
     */
    public static function getEloquentQuery(): Builder
    {
        $companyIds = auth()->user()->companies()->pluck('companies.id')->toArray();

        return parent::getEloquentQuery()
            ->whereIn('company_id', $companyIds);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- HEADER JURNAL ---
                Forms\Components\Section::make('Header Jurnal')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Perusahaan')
                            ->relationship('company', 'name', fn (Builder $query) => 
                                $query->whereIn('id', auth()->user()->companies()->pluck('companies.id'))
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live() 
                            ->columnSpan(3),

                        Forms\Components\DatePicker::make('journal_date')
                            ->label('Tanggal')
                            ->required()
                            ->default(now()),
                        
                        Forms\Components\TextInput::make('reference')
                            ->label('Nomor Referensi')
                            ->placeholder('Contoh: INV-001')
                            ->maxLength(128),
                            
                        Forms\Components\TextInput::make('source')
                            ->label('Sumber')
                            ->default('MANUAL')
                            ->readOnly(),
                            
                        Forms\Components\Textarea::make('memo')
                            ->label('Keterangan')
                            ->columnSpanFull(),
                    ]),

                // --- LINES (DETAIL) ---
                Forms\Components\Section::make('Detail Transaksi')
                    ->hidden(fn (?Journal $record) => $record !== null)
                    ->schema([
                        Forms\Components\Repeater::make('lines')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('account_id')
                                    ->label('Akun')
                                    ->options(function (Get $get) {
                                        $companyId = $get('../../company_id');

                                        if (!$companyId) {
                                            return []; 
                                        }

                                        return \App\Models\Account::query()
                                            ->where('type', 'D')
                                            ->where('company_id', $companyId)
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(4),

                                Forms\Components\Select::make('direction')
                                    ->label('D/K')
                                    ->options([
                                        'debit' => 'Debit',
                                        'credit' => 'Kredit',
                                    ])
                                    ->default('debit')
                                    ->required()
                                    ->live()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('amount')
                                    ->label('Nominal')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->columnSpan(3),
                                
                                Forms\Components\TextInput::make('note')
                                    ->label('Catatan Baris')
                                    ->columnSpan(3),
                            ])
                            ->columns(12)
                            ->defaultItems(2)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updateTotals($get, $set);
                            }),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('total_debit')
                                    ->label('Total Debit')
                                    ->content(fn (Get $get) => number_format($get('total_debit_display') ?? 0, 2)),
                                
                                Forms\Components\Placeholder::make('total_credit')
                                    ->label('Total Kredit')
                                    ->content(fn (Get $get) => number_format($get('total_credit_display') ?? 0, 2))
                                    ->extraAttributes(fn (Get $get) => [
                                        'class' => abs(($get('total_debit_display') ?? 0) - ($get('total_credit_display') ?? 0)) > 0.01 
                                            ? 'text-danger-600 font-bold' 
                                            : 'text-success-600 font-bold' 
                                    ]),
                            ]),
                            
                        Forms\Components\Hidden::make('total_debit_display'),
                        Forms\Components\Hidden::make('total_credit_display'),
                    ]),
            ]);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $lines = collect($get('lines'));
        $debit = $lines->where('direction', 'debit')->sum('amount');
        $credit = $lines->where('direction', 'credit')->sum('amount');
        
        $set('total_debit_display', $debit);
        $set('total_credit_display', $credit);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // TOTALITAS: Tampilkan ID Journal
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                // TOTALITAS: Tampilkan Perusahaan secara permanen
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('journal_date')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Tanggal'),
                    
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->weight('bold')
                    ->label('No. Ref'),
                    
                Tables\Columns\TextColumn::make('memo')
                    ->limit(50)
                    ->label('Keterangan'),
                    
                Tables\Columns\TextColumn::make('total_debit')
                    ->label('Total Nilai')
                    ->money('IDR')
                    ->alignment('right')
                    ->state(function (Journal $record) {
                        return $record->lines()->where('direction', 'debit')->sum('amount');
                    }),
            ])
            // TOTALITAS: Urutkan dari yang TERBARU (ID Descending)
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name', fn (Builder $query) => 
                        $query->whereIn('id', auth()->user()->companies()->pluck('companies.id'))
                    )
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('journal_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('journal_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('journal_date', '<=', $data['until']));
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            LinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournals::route('/'),
            'create' => Pages\CreateJournal::route('/create'),
            'edit' => Pages\EditJournal::route('/{record}/edit'),
        ];
    }
}