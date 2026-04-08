<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NumberingTemplateResource\Pages;
use App\Models\NumberingTemplate;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class NumberingTemplateResource extends Resource
{
    protected static ?string $model = NumberingTemplate::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Accounting'; // Dimasukkan ke Accounting
    protected static ?string $navigationLabel = 'Format Penomoran';
    protected static ?int $navigationSort = 21; // Taruh di urutan paling bawah grup Accounting

    public static function getEloquentQuery(): Builder
    {
        $companyIds = auth()->user()->companies()->pluck('companies.id')->toArray();
        return parent::getEloquentQuery()->whereIn('company_id', $companyIds);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Atur Template Penomoran Otomatis')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Perusahaan')
                            ->options(fn() => Company::whereIn('id', auth()->user()->companies()->pluck('companies.id'))->pluck('name', 'id'))
                            ->required(),

                        Forms\Components\Select::make('source')
                            ->label('Jenis Transaksi')
                            ->options([
                                'Jurnal Umum' => 'Jurnal Umum',
                                'Kas Masuk' => 'Kas Masuk',
                                'Kas Keluar' => 'Kas Keluar',
                                'Transfer Kas' => 'Transfer Kas',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('format')
                            ->label('Format Penomoran')
                            ->placeholder('Contoh: 1 {m}.{y}/AB-K{num}')
                            ->helperText('Gunakan kode ini: {m} = Bulan (03), {y} = Tahun 2 Digit (26), {Y} = Tahun 4 Digit (2026), {num} = Nomor Urut Otomatis')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('pad_length')
                            ->label('Digit Nomor Urut')
                            ->numeric()
                            ->default(2)
                            ->helperText('Contoh: Jika diisi 2, nomor menjadi 01, 02. Jika 3, menjadi 001.')
                            ->required(),

                        Forms\Components\TextInput::make('last_sequence')
                            ->label('Nomor Terakhir')
                            ->numeric()
                            ->default(0)
                            ->helperText('Ganti ke 0 untuk mereset penomoran dari awal.')
                            ->required(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')->label('Perusahaan')->sortable()->badge(),
                Tables\Columns\TextColumn::make('source')->label('Transaksi')->sortable(),
                Tables\Columns\TextColumn::make('format')->label('Format Template')->searchable(),
                Tables\Columns\TextColumn::make('last_sequence')->label('Terakhir Dipakai')->numeric(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Filter Perusahaan'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNumberingTemplates::route('/'),
        ];
    }
}