<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockClassificationResource\Pages;
use App\Models\StockClassification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class StockClassificationResource extends Resource
{
    protected static ?string $model = StockClassification::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Klasifikasi Stok';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Klasifikasi')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Forms\Set $set, $state) => $set('slug', Str::slug($state))),

                    Forms\Components\TextInput::make('slug')
                        ->disabled()
                        ->dehydrated()
                        ->required(),

                    Forms\Components\Select::make('color')
                        ->label('Warna Label')
                        ->options([
                            'success' => 'Hijau (Success)',
                            'warning' => 'Kuning (Warning)',
                            'danger'  => 'Merah (Danger)',
                            'info'    => 'Biru (Info)',
                            'gray'    => 'Abu-abu (Gray)',
                        ])
                        ->required()
                        ->default('gray'),

                    Forms\Components\Textarea::make('description')
                        ->label('Keterangan')
                        ->columnSpanFull(),
                ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Klasifikasi')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn ($record) => $record->color), // Warna dinamis sesuai database

                Tables\Columns\TextColumn::make('description')->label('Ket')->limit(50),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockClassifications::route('/'),
            'create' => Pages\CreateStockClassification::route('/create'),
            'edit' => Pages\EditStockClassification::route('/{record}/edit'),
        ];
    }
}