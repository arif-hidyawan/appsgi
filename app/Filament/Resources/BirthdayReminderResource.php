<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BirthdayReminderResource\Pages;
use App\Models\BirthdayReminder;
use App\Models\Vendor;
use App\Models\VendorContact;
use App\Models\Customer;
use App\Models\CustomerContact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder; 
use Illuminate\Support\Carbon;
use App\Filament\Concerns\HasPermissionPrefix;

class BirthdayReminderResource extends Resource
{
    protected static ?string $model = BirthdayReminder::class;

    protected static ?string $navigationIcon = 'heroicon-o-cake';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Birthday & Anniversary';
    protected static ?string $pluralModelLabel = 'Daftar Ulang Tahun';
    protected static ?int $navigationSort = 18;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'birthday_reminder';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Ucapan')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->required(),

                        Forms\Components\Select::make('type')
                            ->label('Keterangan')
                            ->options([
                                'Vendor' => 'Vendor (Perusahaan)',
                                'PIC Vendor' => 'PIC Vendor',
                                'Customer' => 'Customer (Perusahaan)',
                                'PIC Customer' => 'PIC Customer',
                            ])
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('company_name')
                            ->label('Nama Perusahaan')
                            ->disabled()
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['PIC Vendor', 'PIC Customer'])),

                        Forms\Components\DatePicker::make('date')
                            ->label('Tanggal Ulang Tahun')
                            ->displayFormat('d F Y')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'Belum Dikirim' => 'Belum Dikirim',
                                'Sudah Dikirim' => 'Sudah Dikirim',
                            ])
                            ->default('Belum Dikirim')
                            ->required(),

                        Forms\Components\FileUpload::make('proof')
                            ->label('Bukti Kirim (Foto/Screenshot)')
                            ->image()
                            ->directory('birthday-proofs')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Nama')
                ->searchable()
                ->weight('bold')
                ->description(function (BirthdayReminder $record) {
                    if (in_array($record->type, ['PIC Vendor', 'PIC Customer']) && isset($record->company_name)) {
                        return $record->type . ' - ' . $record->company_name;
                    }
                    return $record->type;
                }),

            Tables\Columns\TextColumn::make('type')
                ->label('Tipe')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'Vendor' => 'info',
                    'Customer' => 'success',
                    'PIC Vendor' => 'warning',
                    'PIC Customer' => 'primary',
                    default => 'gray',
                }),

            Tables\Columns\TextColumn::make('date')
                ->label('Tanggal')
                ->date('d F Y')
                ->sortable()
                ->description(function (BirthdayReminder $record) {
                    $today = now()->startOfDay();
                    $birthdayDate = $record->date->copy()->year($today->year)->startOfDay();
                    $diff = $today->diffInDays($birthdayDate, false);

                    if ($diff > 0) {
                        return 'H-' . $diff;
                    } elseif ($diff === 0) {
                        return 'Hari Ini';
                    } else {
                        return 'Lewat ' . abs($diff) . ' hari';
                    }
                }),

            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'Sudah Dikirim' => 'success',
                    'Belum Dikirim' => 'danger',
                    default => 'gray',
                }),

            Tables\Columns\ImageColumn::make('proof')
                ->label('Bukti')
                ->circular(),
        ])
        ->defaultSort('date', 'asc')
        ->filters([
            // --- FILTER PERIODE CUSTOM (DEFAULT H-7 s/d H+7) ---
            Tables\Filters\Filter::make('periode_custom')
                ->label('Periode Tanggal')
                ->form([
                    Forms\Components\DatePicker::make('date_from')
                        ->label('Dari Tanggal')
                        ->default(now()->subDays(7)), // <--- INI SETTING DEFAULTNYA
                    
                    Forms\Components\DatePicker::make('date_until')
                        ->label('Sampai Tanggal')
                        ->default(now()->addDays(7)), // <--- INI SETTING DEFAULTNYA
                ])
                ->query(function (Builder $query, array $data): Builder {
                    $dateFrom = $data['date_from'];
                    $dateUntil = $data['date_until'];

                    // Jika user menghapus tanggal di filter, tampilkan semua (return query tanpa where)
                    if (! $dateFrom || ! $dateUntil) {
                        return $query;
                    }

                    $start = Carbon::parse($dateFrom);
                    $end = Carbon::parse($dateUntil);

                    $startMonthDay = $start->format('m-d');
                    $endMonthDay = $end->format('m-d');

                    // Logika Lintas Tahun
                    if ($startMonthDay > $endMonthDay) {
                        return $query->where(function (Builder $q) use ($startMonthDay, $endMonthDay) {
                            $q->whereRaw("DATE_FORMAT(date, '%m-%d') >= ?", [$startMonthDay])
                              ->orWhereRaw("DATE_FORMAT(date, '%m-%d') <= ?", [$endMonthDay]);
                        });
                    } 
                    // Logika Normal
                    else {
                        return $query->whereRaw("DATE_FORMAT(date, '%m-%d') >= ?", [$startMonthDay])
                                     ->whereRaw("DATE_FORMAT(date, '%m-%d') <= ?", [$endMonthDay]);
                    }
                })
                // FIX: Menggunakan indicateUsing (bukan indicateResultsUsing)
                ->indicateUsing(function (array $data): ?string {
                    if (! ($data['date_from'] ?? null) || ! ($data['date_until'] ?? null)) {
                        return null;
                    }
                    return 'Periode: ' . Carbon::parse($data['date_from'])->format('d M') . ' s/d ' . Carbon::parse($data['date_until'])->format('d M');
                }),
            // ----------------------------------------------------

            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'Belum Dikirim' => 'Belum Dikirim',
                    'Sudah Dikirim' => 'Sudah Dikirim',
                ]),
            
            Tables\Filters\SelectFilter::make('type')
                ->options([
                    'Vendor' => 'Vendor',
                    'PIC Vendor' => 'PIC Vendor',
                    'Customer' => 'Customer',
                    'PIC Customer' => 'PIC Customer',
                ]),
        ])
        ->headerActions([
            Tables\Actions\Action::make('sync_data')
                ->label('Tarik Data (Vendor & Customer)')
                ->icon('heroicon-m-arrow-path')
                ->color('primary')
                ->action(function () {
                    $count = 0;

                    // 1. VENDOR
                    $vendors = Vendor::whereNotNull('company_anniversary')->get();
                    foreach ($vendors as $vendor) {
                        BirthdayReminder::firstOrCreate(
                            ['name' => $vendor->name, 'type' => 'Vendor'],
                            ['date' => $vendor->company_anniversary, 'status' => 'Belum Dikirim']
                        );
                        $count++;
                    }

                    // 2. PIC VENDOR
                    $vendorContacts = VendorContact::whereNotNull('birth_date')->get();
                    foreach ($vendorContacts as $contact) {
                        $companyName = optional($contact->vendor)->name;
                        BirthdayReminder::firstOrCreate(
                            ['name' => $contact->pic_name, 'type' => 'PIC Vendor'],
                            ['date' => $contact->birth_date, 'status' => 'Belum Dikirim', 'company_name' => $companyName]
                        );
                        $count++;
                    }

                    // 3. CUSTOMER
                    $customers = Customer::whereNotNull('anniversary_date')->get();
                    foreach ($customers as $customer) {
                        BirthdayReminder::firstOrCreate(
                            ['name' => $customer->name, 'type' => 'Customer'],
                            ['date' => $customer->anniversary_date, 'status' => 'Belum Dikirim']
                        );
                        $count++;
                    }

                    // 4. PIC CUSTOMER
                    $customerContacts = CustomerContact::whereNotNull('birth_date')->get();
                    foreach ($customerContacts as $contact) {
                        $companyName = optional($contact->customer)->name;
                        BirthdayReminder::firstOrCreate(
                            ['name' => $contact->pic_name, 'type' => 'PIC Customer'],
                            ['date' => $contact->birth_date, 'status' => 'Belum Dikirim', 'company_name' => $companyName]
                        );
                        $count++;
                    }

                    Notification::make()
                        ->title('Sinkronisasi Berhasil')
                        ->success()
                        ->send();
                }),
        ])
        ->actions([
            Tables\Actions\Action::make('view_proof')
                ->label('Lihat Bukti')
                ->icon('heroicon-o-eye')
                ->modalWidth('lg')
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->action(function () {})
                ->modalContent(function (BirthdayReminder $record) {
                    if (!$record->proof) return '<p class="text-center p-4">Tidak ada bukti tersedia.</p>';
                    $imageUrl = asset('storage/' . $record->proof);
                    return view('filament.components.image-modal', compact('imageUrl'));
                })
                ->visible(fn (BirthdayReminder $record) => !is_null($record->proof)),

            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
}
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBirthdayReminders::route('/'),
            'create' => Pages\CreateBirthdayReminder::route('/create'),
            'edit' => Pages\EditBirthdayReminder::route('/{record}/edit'),
        ];
    }
}