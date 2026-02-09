<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseResource\Pages;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Concerns\HasPermissionPrefix;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office'; // Icon Gedung
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Gudang (Warehouse)';
    protected static ?string $modelLabel = 'Gudang';
    protected static ?int $navigationSort = 7;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'warehouse';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Gudang')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Gudang')
                            ->placeholder('W-001')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Gudang')
                            ->placeholder('Gudang Utama')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('address')
                            ->label('Alamat Lokasi')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Set sebagai Gudang Utama')
                            ->helperText('Otomatis terpilih saat buat transaksi')
                            ->default(false),

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
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Gudang')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Utama')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus')
                    ->color('success'),

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
            'index' => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'edit' => Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
    
    public static function getRelations(): array
    {
        return [];
    }
}