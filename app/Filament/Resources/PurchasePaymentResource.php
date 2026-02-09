<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchasePaymentResource\Pages;
use App\Models\PurchasePayment;
use App\Models\PurchaseInvoice;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Concerns\HasPermissionPrefix;
use Illuminate\Database\Eloquent\Builder;

class PurchasePaymentResource extends Resource
{
    protected static ?string $model = PurchasePayment::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?string $modelLabel = 'Bayar Vendor';
    protected static ?int $navigationSort = 18;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'purchase_payment';

    public static function getEloquentQuery(): Builder
    {
        $companyIds = auth()->user()->companies()->pluck('companies.id')->toArray();
        return parent::getEloquentQuery()->whereIn('company_id', $companyIds);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Input Pengeluaran Kas')
                            ->schema([
                                Forms\Components\TextInput::make('payment_number')
                                    ->label('No. Bukti')
                                    ->default('PAY-OUT-' . now()->format('Ymd') . '-' . rand(100, 999))
                                    ->required()
                                    ->readOnly(),

                                Forms\Components\Select::make('purchase_invoice_id')
                                    ->label('Pilih Tagihan Vendor')
                                    ->relationship('invoice', 'invoice_number', fn(Builder $query) => 
                                        $query->whereIn('company_id', auth()->user()->companies()->pluck('companies.id'))
                                              ->whereIn('status', ['Unpaid', 'Partial'])
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $invoice = PurchaseInvoice::find($state);
                                        if ($invoice) {
                                            $alreadyPaid = $invoice->payments()->sum('amount');
                                            $set('amount', $invoice->grand_total - $alreadyPaid); 
                                            $set('company_id', $invoice->company_id);
                                        }
                                    }),

                                Forms\Components\Hidden::make('company_id'),

                                Forms\Components\Select::make('account_id')
                                    ->label('Bayar Menggunakan (Kas/Bank)')
                                    ->options(fn (Forms\Get $get) => 
                                        Account::where('company_id', $get('company_id'))
                                            ->where('is_cash_bank', true)
                                            ->pluck('name', 'id')
                                    )
                                    ->searchable()
                                    ->required()
                                    ->helperText('Hanya menampilkan akun dengan flag Kas/Bank'),

                                Forms\Components\DatePicker::make('date')
                                    ->label('Tanggal Bayar')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\Select::make('payment_method')
                                    ->label('Metode Pembayaran')
                                    ->options([
                                        'Transfer Bank' => 'Transfer Bank',
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
                                    ->label('No. Ref / Bukti Transfer')
                                    ->placeholder('Contoh: TRF-OUT-001'),

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
                Tables\Columns\TextColumn::make('company.name')->label('Perusahaan')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('payment_number')->searchable()->label('No. Bukti'),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('invoice.invoice_number')->label('No Tagihan'),
                Tables\Columns\TextColumn::make('invoice.vendor.name')->label('Vendor'),
                Tables\Columns\TextColumn::make('amount')->money('IDR')->weight('bold')->color('danger'),
                Tables\Columns\TextColumn::make('bankAccount.name')->label('Sumber Dana'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchasePayments::route('/'),
            'create' => Pages\CreatePurchasePayment::route('/create'),
            'edit' => Pages\EditPurchasePayment::route('/{record}/edit'),
        ];
    }
}