<?php

namespace App\Filament\Resources\JournalResource\RelationManagers; // <--- WAJIB ADA JournalResource

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static ?string $title = 'Rincian Transaksi';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('account_id')
                    ->label('Akun')
                    ->relationship('account', 'name', function (Builder $query) {
                         // Ambil ID Perusahaan dari Jurnal induk
                         $companyId = $this->getOwnerRecord()->company_id;
                         return $query->where('type', 'D')->where('company_id', $companyId);
                    })
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('direction')
                    ->label('D/K')
                    ->options([
                        'debit' => 'Debit',
                        'credit' => 'Kredit',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('amount')
                    ->label('Nominal')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('note')
                    ->label('Catatan')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('note')
            ->columns([
                Tables\Columns\TextColumn::make('account.code')->label('Kode'),
                Tables\Columns\TextColumn::make('account.name')->label('Akun'),
                Tables\Columns\TextColumn::make('direction')->label('Posisi')->badge()
                    ->colors(['success' => 'debit', 'danger' => 'credit']),
                Tables\Columns\TextColumn::make('amount')->label('Nominal')->money('IDR'),
                Tables\Columns\TextColumn::make('note')->label('Catatan'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}