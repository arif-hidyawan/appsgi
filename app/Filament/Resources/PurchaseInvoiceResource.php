<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseInvoiceResource\Pages;
use App\Filament\Resources\PurchaseInvoiceResource\RelationManagers;
use App\Models\PurchaseInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Concerns\HasPermissionPrefix;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class PurchaseInvoiceResource extends Resource
{
    protected static ?string $model = PurchaseInvoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?string $modelLabel = 'Tagihan Vendor';
    protected static ?int $navigationSort = 13;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'purchase_invoice';

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
                        Forms\Components\Section::make('Info Tagihan Vendor')
                            ->schema([
                                Forms\Components\Hidden::make('company_id'),

                                Forms\Components\TextInput::make('invoice_number')
                                    ->label('No. Invoice Vendor')
                                    ->placeholder('Masukkan Nomor Faktur Asli dari Vendor')
                                    ->required()
                                    ->unique(ignoreRecord: true),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'Unpaid' => 'Belum Dibayar (Hutang)',
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

                                Forms\Components\Select::make('purchase_order_id')
                                    ->label('Ref PO')
                                    ->relationship('purchaseOrder', 'po_number')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),

                                Forms\Components\Select::make('vendor_id')
                                    ->label('Vendor')
                                    ->relationship('vendor', 'name')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                                
                                Forms\Components\FileUpload::make('attachment')
                                    ->label('Scan/Foto Faktur')
                                    ->directory('vendor-invoices')
                                    ->openable()
                                    ->columnSpanFull(),
                            ])->columns(2),
                    ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('No. Inv Vendor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date()
                    ->color('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable(),

                Tables\Columns\TextColumn::make('grand_total')
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
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name'),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Unpaid' => 'Belum Lunas',
                        'Paid' => 'Lunas',
                    ]),
            ])
            ->actions([
                // --- ACTION BUAT PEMBAYARAN (Baru) ---
                Tables\Actions\Action::make('create_payment')
                    ->label('Buat Pembayaran')
                    ->icon('heroicon-o-banknotes')
                    ->color('danger') // Merah karena uang keluar
                    ->visible(fn (PurchaseInvoice $record) => $record->status !== 'Paid')
                    ->modalHeading('Bayar Tagihan Vendor')
                    ->form([
                        // Info Perusahaan (Agar tidak salah pakai uang perusahaan lain)
                        Forms\Components\Placeholder::make('company_info')
                            ->label('Sumber Dana (Perusahaan)')
                            ->content(fn (PurchaseInvoice $record) => $record->company->name ?? '-')
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

                        // Sumber Dana (Kas/Bank milik Company tersebut)
                        Forms\Components\Select::make('source_account_id')
                            ->label('Sumber Dana (Kas/Bank)')
                            ->options(function (PurchaseInvoice $record) {
                                return \App\Models\Account::query()
                                    ->where('company_id', $record->company_id) 
                                    ->where('is_cash_bank', 1) 
                                    ->where('type', 'D')       
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Pilih akun Kas/Bank yang digunakan untuk membayar.'),
                            
                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah Bayar')
                            ->prefix('Rp')
                            ->numeric()
                            ->default(fn (PurchaseInvoice $record) => $record->grand_total - $record->payments()->sum('amount'))
                            ->required(),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(2),
                    ])
                    ->action(function (PurchaseInvoice $record, array $data) {
                        return DB::transaction(function () use ($record, $data) {
                            
                            // 1. Buat Record Pembayaran (PurchasePayment)
                            $payment = \App\Models\PurchasePayment::create([
                                'payment_number' => 'PP-' . now()->format('Ymd') . '-' . rand(1000, 9999),
                                'date' => $data['date'],
                                'purchase_invoice_id' => $record->id,
                                'company_id' => $record->company_id,
                                'vendor_id' => $record->vendor_id,
                                'account_id' => $data['source_account_id'], // Simpan akun sumber dana
                                'payment_method' => $data['payment_method'],
                                'amount' => $data['amount'],
                                'notes' => $data['notes'],
                            ]);

                            // 2. Update Status Invoice
                            $totalPaid = $record->payments()->sum('amount') + $data['amount'];
                            if ($totalPaid >= $record->grand_total) {
                                $record->update(['status' => 'Paid']);
                                $record->purchaseOrder?->update(['status' => 'Paid']); // Update PO juga jika perlu
                            } else {
                                $record->update(['status' => 'Partial']);
                            }

                            // 3. Buat Jurnal Akuntansi (Hutang vs Kas)
                            // Cari Akun Hutang Usaha (AP) - Biasa Kode 2-1100
                            $apAccount = \App\Models\Account::where('company_id', $record->company_id)
                                ->where('code', '2-1100') // Sesuaikan kode akun Hutang Usaha
                                ->first();

                            if ($apAccount && $data['source_account_id']) {
                                $journal = \App\Models\Journal::create([
                                    'company_id'   => $record->company_id,
                                    'journal_date' => $data['date'],
                                    'reference'    => $payment->payment_number,
                                    'source'       => 'Purchase Payment',
                                    'memo'         => "Pembayaran Tagihan Vendor {$record->invoice_number} ({$record->vendor->name})",
                                ]);

                                // DEBIT: Hutang Usaha (Hutang Berkurang)
                                $journal->lines()->create([
                                    'account_id' => $apAccount->id,
                                    'direction'  => 'debit',
                                    'amount'     => $data['amount'],
                                    'note'       => 'Pelunasan Hutang Usaha',
                                ]);

                                // KREDIT: Kas/Bank (Uang Keluar)
                                $journal->lines()->create([
                                    'account_id' => $data['source_account_id'],
                                    'direction'  => 'credit',
                                    'amount'     => $data['amount'],
                                    'note'       => 'Pengeluaran Kas/Bank',
                                ]);
                            }

                            Notification::make()->title('Pembayaran Vendor Berhasil Dicatat')->success()->send();

                            // 4. Redirect ke Halaman Purchase Payment
                            return redirect()->to(\App\Filament\Resources\PurchasePaymentResource::getUrl('index'));
                        });
                    }),
                // -------------------------------------

                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListPurchaseInvoices::route('/'),
            'create' => Pages\CreatePurchaseInvoice::route('/create'),
            'edit' => Pages\EditPurchaseInvoice::route('/{record}/edit'),
        ];
    }
}