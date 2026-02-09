<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesInvoiceResource\Pages;
use App\Filament\Resources\SalesInvoiceResource\RelationManagers;
use App\Models\SalesInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Concerns\HasPermissionPrefix;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class SalesInvoiceResource extends Resource
{
    protected static ?string $model = SalesInvoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar'; 
    protected static ?string $navigationGroup = 'Finance'; 
    protected static ?string $modelLabel = 'Faktur Penjualan';
    protected static ?int $navigationSort = 15;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'sales_invoice';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
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
                        Forms\Components\Section::make('Info Tagihan')
                            ->schema([
                                // --- UPDATE: TAMPILKAN COMPANY DI SINI ---
                                Forms\Components\Select::make('company_id')
                                    ->label('Perusahaan')
                                    ->relationship('company', 'name')
                                    ->default(fn () => Auth::user()->company_id)
                                    ->disabled() // Disabled agar tidak diubah user sembarangan
                                    ->dehydrated() // Tetap simpan nilainya ke DB
                                    ->required(),
                                // -----------------------------------------

                                Forms\Components\TextInput::make('invoice_number')
                                    ->label('No. Invoice')
                                    ->default('INV-' . now()->format('Ymd') . '-' . rand(100, 999))
                                    ->required()
                                    ->readOnly(),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'Unpaid' => 'Belum Lunas',
                                        'Partial' => 'Cicil / Sebagian',
                                        'Paid' => 'Lunas',
                                    ])
                                    ->default('Unpaid')
                                    ->required(),

                                Forms\Components\DatePicker::make('date')
                                    ->label('Tanggal Invoice')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\DatePicker::make('due_date')
                                    ->label('Jatuh Tempo')
                                    ->default(now()->addDays(30)) 
                                    ->required(),

                                Forms\Components\Select::make('sales_order_id')
                                    ->label('Ref SO')
                                    ->relationship('salesOrder', 'so_number')
                                    ->disabled()
                                    ->required(),

                                Forms\Components\Select::make('customer_id')
                                    ->label('Customer')
                                    ->relationship('customer', 'name')
                                    ->disabled()
                                    ->required(),
                            ])->columns(2),

                        Forms\Components\Section::make('Rincian Pembayaran')
                            ->schema([
                                Forms\Components\TextInput::make('subtotal_amount')
                                    ->label('Subtotal')
                                    ->prefix('Rp')
                                    ->numeric()
                                    ->readOnly(), 
                            
                                Forms\Components\TextInput::make('tax_amount')
                                    ->label('PPN (Pajak)')
                                    ->prefix('Rp')
                                    ->numeric()
                                    ->readOnly(),

                                Forms\Components\TextInput::make('grand_total')
                                    ->label('Total Tagihan')
                                    ->prefix('Rp')
                                    ->numeric()
                                    ->readOnly()
                                    ->extraInputAttributes(['class' => 'font-bold text-lg']),
                            ])->columns(3),
                    ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('No. Invoice')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                // --- UPDATE: TAMPILKAN COMPANY DI TABEL ---
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-m-building-office')
                    ->sortable()
                    ->searchable()
                    ->toggleable(), // Bisa di hide/show
                // ------------------------------------------
                
                Tables\Columns\TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->color('danger')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('subtotal_amount')
                    ->label('Subtotal')
                    ->money('IDR')
                    ->toggleable(isToggledHiddenByDefault: true), 

                Tables\Columns\TextColumn::make('tax_amount')
                    ->label('PPN')
                    ->money('IDR')
                    ->color('danger') 
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total Tagihan')
                    ->money('IDR')
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Unpaid' => 'danger',
                        'Partial' => 'warning',
                        'Paid' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Unpaid' => 'Belum Lunas',
                        'Paid' => 'Lunas',
                    ]),
                // Filter berdasarkan Company juga bisa ditambahkan jika perlu
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('create_payment')
                    ->label('Catat Pembayaran')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (SalesInvoice $record) => $record->status !== 'Paid') 
                    ->modalHeading('Catat Pembayaran Masuk')
                    ->form([
                        // INFO PERUSAHAAN DI MODAL PEMBAYARAN
                        Forms\Components\Placeholder::make('company_info')
                            ->label('Penerima Pembayaran (Perusahaan)')
                            ->content(fn (SalesInvoice $record) => $record->company->name ?? '-')
                            ->extraAttributes(['class' => 'font-bold text-lg text-primary-600 mb-4']),

                        Forms\Components\DatePicker::make('date')
                            ->label('Tanggal Bayar')
                            ->default(now())
                            ->required(),
                        
                        Forms\Components\Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'Bank Transfer' => 'Bank Transfer',
                                'Cash' => 'Tunai',
                                'Cheque' => 'Cek/Giro',
                            ])
                            ->required(),

                        Forms\Components\Select::make('deposit_to_account_id')
                            ->label('Masuk ke Akun (Kas/Bank)')
                            ->options(function (SalesInvoice $record) {
                                return \App\Models\Account::query()
                                    ->where('company_id', $record->company_id) 
                                    ->where('is_cash_bank', 1) 
                                    ->where('type', 'D')       
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload() 
                            ->required(),
                            
                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah Bayar')
                            ->prefix('Rp')
                            ->numeric()
                            ->default(fn (SalesInvoice $record) => $record->grand_total - $record->payments()->sum('amount'))
                            ->required(),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(2),
                    ])
                    ->action(function (SalesInvoice $record, array $data) {
                        return DB::transaction(function () use ($record, $data) {
                            $payment = \App\Models\SalesPayment::create([
                                'payment_number' => 'PAY-' . now()->format('Ymd') . '-' . rand(1000, 9999),
                                'date' => $data['date'],
                                'sales_invoice_id' => $record->id,
                                'company_id' => $record->company_id,
                                'customer_id' => $record->customer_id,
                                'account_id' => $data['deposit_to_account_id'],
                                'payment_method' => $data['payment_method'],
                                'amount' => $data['amount'],
                                'notes' => $data['notes'],
                            ]);

                            $totalPaid = $record->payments()->sum('amount') + $data['amount'];
                            if ($totalPaid >= $record->grand_total) {
                                $record->update(['status' => 'Paid']);
                                $record->salesOrder?->update(['status' => 'Paid']);
                            } else {
                                $record->update(['status' => 'Partial']);
                            }

                            $arAccount = \App\Models\Account::where('company_id', $record->company_id)->where('code', '1-1210')->first(); 

                            if ($arAccount && $data['deposit_to_account_id']) {
                                $journal = \App\Models\Journal::create([
                                    'company_id'   => $record->company_id,
                                    'journal_date' => $data['date'],
                                    'reference'    => $payment->payment_number,
                                    'source'       => 'Sales Payment',
                                    'memo'         => "Pembayaran Invoice {$record->invoice_number} ({$record->customer->name})",
                                ]);

                                $journal->lines()->create([
                                    'account_id' => $data['deposit_to_account_id'],
                                    'direction'  => 'debit',
                                    'amount'     => $data['amount'],
                                    'note'       => 'Penerimaan Pembayaran',
                                ]);

                                $journal->lines()->create([
                                    'account_id' => $arAccount->id,
                                    'direction'  => 'credit',
                                    'amount'     => $data['amount'],
                                    'note'       => 'Pelunasan Piutang',
                                ]);
                            }

                            Notification::make()->title('Pembayaran Berhasil Dicatat')->success()->send();

                            return redirect()->to(\App\Filament\Resources\SalesPaymentResource::getUrl('index'));
                        });
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('print')
                    ->label('Cetak Invoice')
                    ->icon('heroicon-o-printer')
                    ->url(fn (SalesInvoice $record) => route('print.invoice', $record))
                    ->openUrlInNewTab(),
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
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesInvoices::route('/'),
            'create' => Pages\CreateSalesInvoice::route('/create'),
            'edit' => Pages\EditSalesInvoice::route('/{record}/edit'),
        ];
    }
}