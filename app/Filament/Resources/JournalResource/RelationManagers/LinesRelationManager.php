<?php

namespace App\Filament\Resources\JournalResource\RelationManagers; // <--- WAJIB ADA JournalResource

use App\Models\Account;
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
                    // Menggunakan options() array multi-dimensi untuk membuat Tree / OptGroup otomatis
                    ->options(function () {
                        // Ambil ID Perusahaan dari Jurnal induk
                        $companyId = $this->getOwnerRecord()->company_id;
                        
                        // Ambil semua akun bertipe Detail beserta relasi parent-nya
                        $accounts = Account::query()
                            ->where('type', 'D')
                            ->where('company_id', $companyId)
                            ->with('parent') 
                            ->orderBy('code')
                            ->get();
                            
                        $options = [];
                        foreach ($accounts as $account) {
                            // Tentukan Nama Induk (Grup)
                            $groupName = $account->parent ? "{$account->parent->code} - {$account->parent->name}" : 'Tanpa Induk';
                            
                            // Masukkan akun ke dalam grup tersebut (Format: Kode - Nama)
                            $options[$groupName][$account->id] = "{$account->code} - {$account->name}";
                        }
                        
                        return $options;
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan(4), // Lebar kolom form disesuaikan

                Forms\Components\Select::make('direction')
                    ->label('D/K')
                    ->options([
                        'debit' => 'Debit',
                        'credit' => 'Kredit',
                    ])
                    ->default('debit') // Default ke debit biar cepat
                    ->required()
                    ->columnSpan(2),

                Forms\Components\TextInput::make('amount')
                    ->label('Nominal')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->columnSpan(3),

                Forms\Components\TextInput::make('note')
                    ->label('Catatan')
                    ->maxLength(255)
                    ->columnSpan(3),
            ])
            ->columns(12); // Grid 12 kolom agar inputannya sejajar rapi 1 baris
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('note')
            ->columns([
                Tables\Columns\TextColumn::make('account.code')
                    ->label('Kode')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('account.name')
                    ->label('Akun')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('direction')
                    ->label('Posisi')
                    ->badge()
                    ->colors([
                        'success' => 'debit',
                        'danger' => 'credit'
                    ]),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->alignment('right'),
                    
                Tables\Columns\TextColumn::make('note')
                    ->label('Catatan'),
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