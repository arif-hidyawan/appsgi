<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GenerateSalesInvoiceResource\Pages;
use App\Models\SalesOrder;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Account;
use App\Models\Journal;
use App\Filament\Resources\SalesInvoiceResource;
use App\Filament\Resources\SalesOrderResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class GenerateSalesInvoiceResource extends Resource
{
    protected static ?string $model = SalesOrder::class;

    protected static ?string $slug = 'finance/buat-faktur-penjualan';

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Buat Faktur Jual';
    protected static ?string $modelLabel = 'Tagihan SO';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotIn('status', ['Invoiced', 'Paid', 'Cancelled']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('so_number')
                    ->label('No. SO')
                    ->weight('bold')
                    ->color('primary')
                    ->searchable()
                    ->sortable()
                    ->url(fn (SalesOrder $record): string => SalesOrderResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('date')
                    ->date('d M Y')
                    ->label('Tanggal Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan Internal')
                    ->searchable()
                    ->sortable()
                    ->toggleable(), 

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total Nilai SO')
                    ->money('IDR')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'New' => 'Baru',
                        'Processed' => 'Diproses',
                        'Siap Kirim' => 'Siap Kirim',
                        'Completed' => 'Selesai (Dikirim)',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'New' => 'info',
                        'Processed' => 'warning',
                        'Siap Kirim' => 'success',
                        'Completed' => 'primary',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('payment_terms')
                    ->label('Termin')
                    ->badge()
                    ->color('info')
                    ->toggleable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Filter Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('create_invoice_single')
                    ->label('Buat Faktur')
                    ->icon('heroicon-o-receipt-percent')
                    ->color('success')
                    ->button()
                    ->modalHeading('Terbitkan Faktur Penjualan')
                    ->form(self::getInvoiceFormSchema())
                    ->action(function (SalesOrder $record, array $data) {
                        $records = collect([$record]); 
                        return self::processInvoice($records, $data);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('create_invoice_bulk')
                        ->label('Gabung Jadi 1 Faktur')
                        ->icon('heroicon-o-rectangle-stack')
                        ->color('success')
                        ->modalHeading('Terbitkan Faktur Gabungan (Consolidated Invoice)')
                        ->modalDescription('Pastikan Anda hanya mencentang SO dengan Customer dan Perusahaan Internal yang SAMA.')
                        ->form(self::getInvoiceFormSchema())
                        ->action(function (Collection $records, array $data) {
                            return self::processInvoice($records, $data);
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    private static function getInvoiceFormSchema(): array
    {
        return [
            Forms\Components\Select::make('invoice_type')
                ->label('Jenis Penagihan')
                ->options([
                    'DP' => 'Tagih Down Payment (DP)',
                    'Full' => 'Tagih Pelunasan (Sisa Tagihan / Full)',
                ])
                ->default('Full')
                ->required()
                ->live(),

            Forms\Components\TextInput::make('dp_percentage')
                ->label('Persentase DP (%)')
                ->numeric()
                ->minValue(1)
                ->maxValue(99)
                ->default(50)
                ->suffix('%')
                ->visible(fn (Forms\Get $get) => $get('invoice_type') === 'DP')
                ->required(fn (Forms\Get $get) => $get('invoice_type') === 'DP'),

            Forms\Components\DatePicker::make('due_date')
                ->label('Jatuh Tempo')
                ->default(now()->addDays(14))
                ->required(),
                
            Forms\Components\Textarea::make('notes')
                ->label('Catatan Faktur (Opsional)'),
        ];
    }

    private static function processInvoice(Collection $records, array $data)
    {
        $firstRecord = $records->first();
        $isSameCustomer = $records->every(fn($r) => $r->customer_id === $firstRecord->customer_id);
        $isSameCompany = $records->every(fn($r) => $r->company_id === $firstRecord->company_id);

        if (!$isSameCustomer || !$isSameCompany) {
            Notification::make()
                ->title('Gagal Menggabungkan')
                ->body('Anda hanya bisa menggabungkan SO yang memiliki Customer dan Perusahaan Internal yang SAMA.')
                ->danger()
                ->send();
            return;
        }

        // --- KALKULASI TOTAL SO ---
        $totalSubtotalSO = $records->sum('subtotal_amount');
        $totalTaxSO = $records->sum('tax_amount');
        $totalGrandSO = $records->sum('grand_total');

        // --- CARI TOTAL YANG SUDAH PERNAH DITAGIH (DP SEBELUMNYA) ---
        $alreadyInvoicedSubtotal = 0;
        $alreadyInvoicedTax = 0;

        foreach ($records as $so) {
            // Cek di tabel item invoice yang terhubung ke SO ini
            $invoicedSub = SalesInvoiceItem::where('sales_order_id', $so->id)->sum('subtotal');
            // Hitung rasio pajaknya
            $taxRatio = $so->subtotal_amount > 0 ? ($so->tax_amount / $so->subtotal_amount) : 0;
            
            $alreadyInvoicedSubtotal += $invoicedSub;
            $alreadyInvoicedTax += ($invoicedSub * $taxRatio);
        }
        $alreadyInvoicedGrandTotal = $alreadyInvoicedSubtotal + $alreadyInvoicedTax;

        $isDP = $data['invoice_type'] === 'DP';

        // --- TENTUKAN NILAI INVOICE SAAT INI ---
        if ($isDP) {
            $multiplier = $data['dp_percentage'] / 100;
            $invoiceSubtotal = $totalSubtotalSO * $multiplier;
            $invoiceTax = $totalTaxSO * $multiplier;
            $invoiceGrandTotal = $totalGrandSO * $multiplier;
        } else {
            // PELUNASAN: Total SO dikurangi yang sudah ditagih (Sisa)
            $invoiceSubtotal = $totalSubtotalSO - $alreadyInvoicedSubtotal;
            $invoiceTax = $totalTaxSO - $alreadyInvoicedTax;
            $invoiceGrandTotal = $totalGrandSO - $alreadyInvoicedGrandTotal;
        }

        // Validasi jika tidak ada sisa tagihan
        if ($invoiceGrandTotal <= 0) {
            Notification::make()
                ->title('Tidak Ada Sisa Tagihan')
                ->body('SO ini sudah ditagihkan secara penuh sebelumnya.')
                ->warning()
                ->send();
            return;
        }

        return DB::transaction(function () use ($records, $firstRecord, $data, $invoiceSubtotal, $invoiceTax, $invoiceGrandTotal, $totalSubtotalSO, $alreadyInvoicedSubtotal, $totalGrandSO, $isDP) {
            
            $invoice = SalesInvoice::create([
                'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . rand(1000, 9999),
                'date' => now(),
                'due_date' => $data['due_date'],
                'sales_order_id' => $records->count() === 1 ? $firstRecord->id : null, 
                'customer_id' => $firstRecord->customer_id,
                'company_id' => $firstRecord->company_id, 
                'subtotal_amount' => $invoiceSubtotal,
                'tax_amount' => $invoiceTax,
                'grand_total' => $invoiceGrandTotal,
                'status' => 'Unpaid',
                'notes' => $data['notes'] ?? ($records->count() > 1 ? 'Faktur Gabungan untuk SO: ' . $records->pluck('so_number')->implode(', ') : null),
                'is_dp' => $isDP, 
            ]);

            // Rasio harga item untuk disisipkan ke rincian invoice
            $itemMultiplier = $totalGrandSO > 0 ? ($invoiceGrandTotal / $totalGrandSO) : 1;

            foreach ($records as $so) {
                foreach ($so->items as $item) {
                    $itemQty = $item->qty;
                    // Harga unit dikali persentase tagihan (DP atau Sisa)
                    $itemUnitPrice = $item->unit_price * $itemMultiplier;
                    
                    $invoice->items()->create([
                        'product_id' => $item->product_id,
                        'qty' => $itemQty,
                        'unit_price' => $itemUnitPrice,
                        'subtotal' => $itemQty * $itemUnitPrice,
                        'sales_order_id' => $so->id, 
                    ]);
                }
            }

            // --- AKUN DATABASE ---
            $arAccount = Account::where('company_id', $firstRecord->company_id)->where('code', '1103.100')->first(); // Piutang Usaha
            $salesAccount = Account::where('company_id', $firstRecord->company_id)->where('code', '4101.001')->first(); // Penjualan Barang
            $taxAccount = Account::where('company_id', $firstRecord->company_id)->where('code', '2103.001')->first(); // PPN Keluaran
            $dpAccount = Account::where('company_id', $firstRecord->company_id)->where('code', '2102.001')->first(); // Pendapatan Diterima di Muka (Uang Muka)

            if ($arAccount) {
                $journal = Journal::create([
                    'company_id'   => $firstRecord->company_id,
                    'journal_date' => now(), 
                    'reference'    => $invoice->invoice_number,
                    'source'       => 'Sales Invoice',
                    'memo'         => ($isDP ? "Tagihan DP " . $data['dp_percentage'] . "%" : "Tagihan Pelunasan") . " - {$firstRecord->customer->name}",
                ]);

                // DEBIT: Piutang Usaha (Sejumlah sisa yang ditagih saat ini)
                $journal->lines()->create([
                    'account_id' => $arAccount->id,
                    'direction'  => 'debit',
                    'amount'     => $invoiceGrandTotal,
                    'note'       => 'Piutang Penjualan',
                ]);

                if ($isDP && $dpAccount) {
                    // KREDIT (JIKA DP): Masuk ke Uang Muka (Belum jadi penjualan penuh)
                    $journal->lines()->create([
                        'account_id' => $dpAccount->id,
                        'direction'  => 'credit',
                        'amount'     => $invoiceSubtotal, 
                        'note'       => 'Uang Muka Pelanggan',
                    ]);
                    
                    // KREDIT: PPN Keluaran
                    if ($invoiceTax > 0 && $taxAccount) {
                        $journal->lines()->create([
                            'account_id' => $taxAccount->id,
                            'direction'  => 'credit',
                            'amount'     => $invoiceTax,
                            'note'       => 'PPN Keluaran',
                        ]);
                    }
                } else {
                    // --- JIKA PELUNASAN (FULL) ---

                    // DEBIT: Balikkan saldo Uang Muka DP (Jika sebelumnya ada DP)
                    if ($alreadyInvoicedSubtotal > 0 && $dpAccount) {
                        $journal->lines()->create([
                            'account_id' => $dpAccount->id,
                            'direction'  => 'debit',
                            'amount'     => $alreadyInvoicedSubtotal, 
                            'note'       => 'Pembalikan Uang Muka Pelanggan',
                        ]);
                    }

                    // KREDIT: Penjualan Barang (MENGAKUI FULL 100% PENDAPATAN)
                    if ($salesAccount) {
                        $journal->lines()->create([
                            'account_id' => $salesAccount->id,
                            'direction'  => 'credit',
                            'amount'     => $totalSubtotalSO, 
                            'note'       => 'Pendapatan Penjualan',
                        ]);
                    }

                    // KREDIT: PPN Keluaran (Hanya sisa PPN yang ditagihkan saat pelunasan)
                    if ($invoiceTax > 0 && $taxAccount) {
                        $journal->lines()->create([
                            'account_id' => $taxAccount->id,
                            'direction'  => 'credit',
                            'amount'     => $invoiceTax,
                            'note'       => 'PPN Keluaran',
                        ]);
                    }
                }
            }

            // Update Status SO
            if (!$isDP) {
                foreach ($records as $so) {
                    $so->update(['status' => 'Invoiced']);
                }
            }

            Notification::make()
                ->title('Faktur Berhasil Dibuat')
                ->body('Faktur ' . $invoice->invoice_number . ' telah diterbitkan.')
                ->success()
                ->send();

            return redirect()->to(SalesInvoiceResource::getUrl('edit', ['record' => $invoice->id]));
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageGenerateSalesInvoices::route('/'),
        ];
    }
}