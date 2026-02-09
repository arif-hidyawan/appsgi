<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Filament\Concerns\HasPermissionPrefix;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale'; // Icon Timbangan/Ukuran
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Satuan (Unit)';
    protected static ?string $modelLabel = 'Satuan';
    protected static ?int $navigationSort = 5;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'unit';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Satuan')
                            ->placeholder('Contoh: Pieces, Kilogram, Karton')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('code')
                            ->label('Kode Singkatan')
                            ->placeholder('Contoh: PCS, KG, KTN')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(10)
                            // Paksa jadi Huruf Besar saat user mengetik
                            ->dehydrateStateUsing(fn (string $state): string => strtoupper($state)),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Satuan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->badge() // Tampil seperti label kecil
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
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
            'index' => Pages\ListUnits::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
            'edit' => Pages\EditUnit::route('/{record}/edit'),
        ];
    }
    
    public static function getRelations(): array
    {
        return [];
    }
}