<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TemporaryCustomerResource\Pages;
use App\Models\TemporaryCustomer;
use App\Models\Customer;
use App\Models\CustomerContact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class TemporaryCustomerResource extends Resource
{
    protected static ?string $model = TemporaryCustomer::class;

    protected static ?string $navigationGroup = 'Master Data'; // Sesuaikan dengan grup menu Anda
    protected static ?string $navigationLabel = 'Approval Customer';
    protected static ?string $modelLabel = 'Approval Customer';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function form(Form $form): Form
    {
        // Form ini sifatnya lebih ke Read-Only untuk Review oleh Admin
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Perusahaan')->schema([
                    Forms\Components\TextInput::make('company_name')->label('Nama Perusahaan')->required(),
                    Forms\Components\TextInput::make('email')->label('Email')->email(),
                    Forms\Components\TextInput::make('business_type')->label('Jenis Usaha'),
                    Forms\Components\TextInput::make('company_phone')->label('No. Telepon'),
                    Forms\Components\Textarea::make('address')->label('Alamat Lengkap')->columnSpanFull(),
                    Forms\Components\TextInput::make('district')->label('Kecamatan'),
                    Forms\Components\TextInput::make('city')->label('Kota/Kabupaten'),
                    Forms\Components\TextInput::make('province')->label('Provinsi'),
                ])->columns(2),

                Forms\Components\Section::make('Informasi PIC & Kerja Sama')->schema([
                    Forms\Components\TextInput::make('pic_name')->label('Nama PIC'),
                    Forms\Components\TextInput::make('pic_position')->label('Jabatan PIC'),
                    Forms\Components\TextInput::make('pic_phone')->label('No. Telepon PIC'),
                    Forms\Components\TextInput::make('business_scope')->label('Lingkup Usaha'),
                    Forms\Components\TextInput::make('payment_terms')->label('Rencana Term Pembayaran PO'),
                ])->columns(2),

                Forms\Components\Section::make('Dokumen Pendukung')->schema([
                    Forms\Components\FileUpload::make('supporting_documents')
                        ->label('File Pendukung')
                        ->multiple()
                        ->directory('customer_documents')
                        ->openable() // Memungkinkan admin membuka file di tab baru
                        ->downloadable() // Memungkinkan admin mendownload file
                        ->columnSpanFull(),
                ]),
                
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->required()
                    ->disabled() // Di-disable karena status akan diubah lewat Action Buttons di tabel
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')->label('Perusahaan')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('pic_name')->label('Nama PIC')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Tgl Register')->dateTime('d M Y H:i')->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending'), // Default menampilkan yang pending saja
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Review'),
                Tables\Actions\EditAction::make(),
                
                // --- ACTION APPROVAL OTOMATIS ---
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Customer')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui customer ini? Data akan otomatis disalin ke tabel Master Customer.')
                    // Sembunyikan tombol jika status sudah approved/rejected
                    ->hidden(fn (TemporaryCustomer $record) => $record->status !== 'pending')
                    ->action(function (TemporaryCustomer $record) {
                        DB::transaction(function () use ($record) {
                            // 1. Buat Data di tabel Customers
                            $customer = Customer::create([
                                // Generate kode customer otomatis (bisa disesuaikan formatnya)
                                'customer_code' => 'CUST-' . date('Ymd') . '-' . str_pad($record->id, 4, '0', STR_PAD_LEFT),
                                'name' => $record->company_name,
                                'email' => $record->email,
                                'phone' => $record->company_phone,
                                'billing_address' => $record->address,
                                'district' => $record->district,
                                'city' => $record->city,
                                'province' => $record->province,
                                'business_type' => $record->business_type,
                                'business_scope' => $record->business_scope,
                                'payment_terms' => $record->payment_terms,
                                'supporting_documents' => $record->supporting_documents,
                                'is_active' => 1,
                            ]);

                            // 2. Buat Data di tabel Customer Contacts
                            CustomerContact::create([
                                'customer_id' => $customer->id,
                                'pic_name' => $record->pic_name,
                                'position' => $record->pic_position,
                                'phone' => $record->pic_phone,
                            ]);

                            // 3. Update status temporary customer menjadi approved
                            $record->update(['status' => 'approved']);
                        });

                        Notification::make()
                            ->title('Customer Berhasil Di-Approve!')
                            ->success()
                            ->send();
                    }),

                // --- ACTION REJECT ---
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->hidden(fn (TemporaryCustomer $record) => $record->status !== 'pending')
                    ->action(function (TemporaryCustomer $record) {
                        $record->update(['status' => 'rejected']);
                        Notification::make()
                            ->title('Registrasi Customer Ditolak.')
                            ->danger()
                            ->send();
                    }),
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
            'index' => Pages\ListTemporaryCustomers::route('/'),
            'create' => Pages\CreateTemporaryCustomer::route('/create'),
            'edit' => Pages\EditTemporaryCustomer::route('/{record}/edit'),
        ];
    }
}