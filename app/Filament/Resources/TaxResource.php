<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxResource\Pages;
use App\Models\Tax;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Filament\Concerns\HasPermissionPrefix;

class TaxResource extends Resource
{
    protected static ?string $model = Tax::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes'; // Icon Uang/Pajak
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Pajak (Tax)';
    protected static ?string $modelLabel = 'Pajak';
    protected static ?int $navigationSort = 6;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'tax';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pajak')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Pajak')
                            ->placeholder('Contoh: PPN 11%')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('rate')
                            ->label('Tarif (%)')
                            ->numeric()
                            ->suffix('%') // Tanda persen di kanan
                            ->placeholder('11')
                            ->required()
                            ->maxValue(100)
                            ->minValue(0),

                        Forms\Components\TextInput::make('priority')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0)
                            ->helperText('Semakin kecil angka, semakin atas urutannya.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->inline(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pajak')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('rate')
                    ->label('Tarif')
                    ->suffix('%')
                    ->sortable()
                    ->badge()
                    ->color('danger'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Update Terakhir')
                    ->dateTime()
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxes::route('/'),
            'create' => Pages\CreateTax::route('/create'),
            'edit' => Pages\EditTax::route('/{record}/edit'),
        ];
    }
}