<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\VendorContact;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $title = 'Kontak & Kategori';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // SESUAI DB: pic_name
                Forms\Components\TextInput::make('pic_name')
                    ->label('Nama PIC')
                    ->required()
                    ->maxLength(255),
                
                // SESUAI DB: phone
                Forms\Components\TextInput::make('phone')
                    ->label('No. HP / WA')
                    ->tel()
                    ->maxLength(255),

                // SESUAI DB: birth_date
                Forms\Components\DatePicker::make('birth_date')
                    ->label('Tanggal Lahir')
                    ->native(false)
                    ->displayFormat('d F Y') // Tampilan: 17 Agustus 1945
                    ->maxDate(now()),

                // SESUAI DB: email
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),

                // SESUAI DB: category (JSON)
                Forms\Components\TagsInput::make('category')
                    ->label('Kategori')
                    ->placeholder('Ketik kategori lalu Enter')
                    ->suggestions(function () {
                        // Ambil saran dari data yang sudah ada di DB
                        $data = VendorContact::query()
                            ->whereNotNull('category')
                            ->pluck('category')
                            ->toArray();
                        
                        return collect($data)->flatten()->unique()->filter()->toArray();
                    })
                    ->columnSpanFull(),

                // SESUAI DB: vendor_info
                Forms\Components\Textarea::make('vendor_info')
                    ->label('Info Tambahan')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('pic_name') // Ubah jadi pic_name
            ->columns([
                // Ubah jadi pic_name
                Tables\Columns\TextColumn::make('pic_name')
                    ->label('PIC')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('birth_date')
                    ->label('Tgl Lahir')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->separator(',')
                    ->limitList(3)
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('email')
                    ->icon('heroicon-m-envelope')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('vendor_info')
                    ->label('Info')
                    ->limit(30)
                    ->tooltip(fn (Tables\Columns\TextColumn $column): ?string => $column->getState())
                    ->toggleable(isToggledHiddenByDefault: true), 
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Tambah Kontak'),
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