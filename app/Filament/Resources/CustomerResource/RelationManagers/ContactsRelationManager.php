<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $title = 'Daftar PIC Customer';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('pic_name')
                    ->label('Nama PIC')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('position')
                    ->label('Jabatan')
                    ->placeholder('Contoh: Purchasing, Direktur'),

                Forms\Components\DatePicker::make('birth_date')
                    ->label('Tanggal Lahir')
                    ->displayFormat('d F Y')
                    ->native(false),

                Forms\Components\TextInput::make('phone')
                    ->label('No HP / WA')
                    ->required()
                    ->tel(),

                Forms\Components\TextInput::make('email')
                    ->email(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('pic_name')
            ->columns([
                Tables\Columns\TextColumn::make('pic_name')
                    ->label('Nama PIC')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('position')
                    ->label('Jabatan')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('birth_date')
                    ->label('Tgl Lahir')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->icon('heroicon-m-phone'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Tambah PIC'),
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
}