<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesPaymentResource\Pages;
use App\Models\SalesPayment;
use App\Models\SalesInvoice;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Concerns\HasPermissionPrefix;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class SalesPaymentResource extends Resource
{
    protected static ?string $model = SalesPayment::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?string $modelLabel = 'Pelunasan Piutang';
    protected static ?int $navigationSort = 16;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'sales_payment';

    public static function getEloquentQuery(): Builder
    {
        // TOTALITAS: Filter query sesuai akses perusahaan user (pivot)
        $companyIds = auth()->user()->companies()->pluck('companies.id')->toArray();
        return parent::getEloquentQuery()->whereIn('company_id', $companyIds);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Input Pembayaran')
                            ->schema([
                                Forms\Components\TextInput::make('payment_number')
                                    ->label('No. Kwitansi')
                                    ->default('PAY-' . now()->format('Ymd') . '-' . rand(100, 999))
                                    ->readOnly()
                                    ->required(),

                                // PILIH INVOICE (Trigger Utama)
                                Forms\Components\Select::make('sales_invoice_id')
                                    ->label('Pilih Invoice')
                                    ->relationship('invoice', 'invoice_number', fn(Builder $query) => 
                                        $query->whereIn('company_id', auth()->user()->companies()->pluck('companies.id'))
                                              ->whereIn('status', ['Unpaid', 'Partial'])
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live() // Live agar bisa trigger update field lain
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $invoice = SalesInvoice::find($state);
                                        if ($invoice) {
                                            $alreadyPaid = $invoice->payments()->sum('amount');
                                            
                                            // Otomatis isi data dari Invoice
                                            $set('amount', $invoice->grand_total - $alreadyPaid);
                                            $set('company_id', $invoice->company_id);
                                            $set('customer_id', $invoice->customer_id); // Penting!
                                        }
                                    }),

                                // FIELD HIDDEN (Disimpan otomatis)
                                Forms\Components\Hidden::make('company_id'),
                                Forms\Components\Hidden::make('customer_id'),

                                // PILIH AKUN BANK (Terfilter sesuai Company Invoice)
                                Forms\Components\Select::make('account_id')
                                    ->label('Masuk ke Akun (Kas/Bank)')
                                    ->options(function (Forms\Get $get) {
                                        $companyId = $get('company_id');
                                        if (!$companyId) return []; // Kosongkan jika invoice belum dipilih

                                        return Account::where('company_id', $companyId)
                                            ->where('is_cash_bank', 1) // Hanya akun Kas/Bank
                                            ->where('type', 'D')       // Hanya akun Detail
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->helperText('Pilih akun Kas atau Bank penampung dana'),

                                Forms\Components\DatePicker::make('date')
                                    ->label('Tanggal Terima')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\Select::make('payment_method')
                                    ->label('Metode Pembayaran')
                                    ->options([
                                        'Bank Transfer' => 'Bank Transfer',
                                        'Transfer Mandiri' => 'Transfer Mandiri',
                                        'Transfer BCA' => 'Transfer BCA',
                                        'Cash' => 'Tunai / Cash',
                                        'Cheque' => 'Cek / Giro',
                                    ])
                                    ->required(),

                                Forms\Components\TextInput::make('amount')
                                    ->label('Jumlah Dibayar')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(),

                                Forms\Components\TextInput::make('reference_number')
                                    ->label('No. Referensi / Bukti Trf')
                                    ->placeholder('Contoh: TRF-123xxxx'),

                                Forms\Components\Textarea::make('notes')
                                    ->label('Catatan')
                                    ->columnSpanFull(),
                            ])->columns(2),
                    ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_number')
                    ->searchable()
                    ->label('No. Kwitansi')
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Tanggal'),

                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('No Invoice')
                    ->searchable(),

                Tables\Columns\TextColumn::make('invoice.customer.name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->weight('bold')
                    ->color('success')
                    ->label('Jumlah'),

                Tables\Columns\TextColumn::make('bankAccount.name') // Pastikan relasi bankAccount ada di model SalesPayment
                    ->label('Diterima di')
                    ->icon('heroicon-m-building-library')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode')
                    ->badge(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('print')
                    ->label('Cetak Kwitansi')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (SalesPayment $record) => route('print.payment', $record))
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListSalesPayments::route('/'),
            'create' => Pages\CreateSalesPayment::route('/create'),
            'edit' => Pages\EditSalesPayment::route('/{record}/edit'),
        ];
    }
}