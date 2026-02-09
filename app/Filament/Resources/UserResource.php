<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Filament\Resources\Pages\CreateRecord;

use App\Filament\Concerns\HasPermissionPrefix;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $navigationLabel = 'Pengguna';
    protected static ?string $modelLabel = 'Pengguna';
    protected static ?string $pluralModelLabel = 'Pengguna';
    protected static ?int $navigationSort = 92;
    protected static ?string $recordTitleAttribute = 'name';

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'user';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Akun')
                    ->description('Masukan detail login dan kontak pengguna.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Alamat Email')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->maxLength(255),

                        // --- TAMBAHAN BARU: NO WA & WA LID ---
                        Forms\Components\TextInput::make('no_wa')
                            ->label('Nomor WhatsApp')
                            ->tel() // Menampilkan keyboard angka di mobile
                            ->placeholder('Contoh: 62812345678')
                            ->maxLength(20),

                     
                        // -------------------------------------

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->placeholder(fn ($livewire) => $livewire instanceof CreateRecord ? '' : 'Kosongkan jika tidak ingin mengubah')
                            ->required(fn ($livewire) => $livewire instanceof CreateRecord),
                    ])->columns(1),

                Forms\Components\Section::make('Akses & Organisasi')
                    ->description('Atur hak akses dan perusahaan pengguna.')
                    ->schema([
                        // INPUT PERUSAHAAN (Tetap Multiple)
                        Forms\Components\Select::make('companies')
                            ->label('Perusahaan')
                            ->relationship('companies', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Pengguna dapat mengakses data perusahaan yang dipilih disini.'),

                        // INPUT ROLE (SINGLE)
                        Forms\Components\Select::make('roles') 
                            ->label('Role')
                            ->options(Role::pluck('name', 'id')) 
                            ->searchable()
                            ->preload()
                            ->required()
                            ->formatStateUsing(fn ($record) => $record?->roles()->first()?->id)
                            ->saveRelationshipsUsing(fn ($record, $state) => $record->roles()->sync([$state]))
                            ->dehydrated(false)
                            ->helperText('Tentukan level akses pengguna (Pilih satu).'),
                            
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->icon('heroicon-m-envelope')
                    ->searchable(),

                // --- TAMBAHAN BARU: NO WA ---
                Tables\Columns\TextColumn::make('no_wa')
                    ->label('WhatsApp')
                    ->icon('heroicon-m-phone')
                    ->searchable()
                    ->placeholder('-'),
                // ----------------------------

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('companies.name')
                    ->label('Perusahaan')
                    ->badge()
                    ->color('success')
                    ->separator(',')
                    ->limitList(2)
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}