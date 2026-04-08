<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InputVendorBillResource\Pages;
use App\Models\PurchaseOrder;
use App\Models\PurchaseInvoice;
use App\Models\Account;
use App\Models\Journal;
use App\Filament\Resources\PurchaseInvoiceResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class InputVendorBillResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;
    protected static ?string $slug = 'finance/input-tagihan-vendor';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Input Tagihan Vendor';
    protected static ?string $modelLabel = 'Tagihan Vendor';
    protected static ?int $navigationSort = 13;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotIn('status', ['Draft', 'Cancelled', 'Billed', 'Paid']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('No. PO')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Tgl Order')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan Internal')
                    ->icon('heroicon-m-building-office')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Nilai Tagihan')
                    ->money('IDR')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Ordered' => 'Dipesan',
                        'Partial' => 'Diterima Sebagian',
                        'Received' => 'Diterima Penuh',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Ordered' => 'warning',
                        'Partial' => 'info',
                        'Received' => 'success',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('vendor_id')
                    ->label('Filter Vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('create_bill')
                    ->label('Input Tagihan')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('primary')
                    ->button()
                    ->requiresConfirmation()
                    ->modalHeading('Rekam Tagihan Vendor & Jurnal Otomatis')
                    ->form([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Nomor Faktur Vendor')
                            ->required(),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Jatuh Tempo')
                            ->default(now()->addDays(30))
                            ->required(),
                    ])
                    ->action(function (PurchaseOrder $record, array $data) {
                        return DB::transaction(function () use ($record, $data) {
                            
                            $invoice = PurchaseInvoice::create([
                                'invoice_number' => $data['invoice_number'],
                                'date' => now(),
                                'due_date' => $data['due_date'],
                                'purchase_order_id' => $record->id,
                                'vendor_id' => $record->vendor_id,
                                'company_id' => $record->company_id,
                                'grand_total' => $record->grand_total,
                                'status' => 'Unpaid',
                            ]);

                            foreach ($record->items as $item) {
                                $invoice->items()->create([
                                    'product_id' => $item->product_id,
                                    'qty' => $item->qty,
                                    'unit_price' => $item->unit_price,
                                    'subtotal' => $item->subtotal,
                                ]);
                            }

                            // --- KONFIGURASI KODE AKUN ---
                            // Mas Arif, silakan ganti variabel ini sesuai kode akun yang baru dibuat tadi
                            $unbilledCode = '2104.008'; // Contoh: 2104.008
                            $apCode       = '2101.001'; // Hutang Usaha

                            $unbilledAccount = Account::where('company_id', $record->company_id)
                                ->where('code', $unbilledCode)->first(); 
                            
                            $apAccount = Account::where('company_id', $record->company_id)
                                ->where('code', $apCode)->first(); 

                            if ($unbilledAccount && $apAccount) {
                                $journal = Journal::create([
                                    'company_id'   => $record->company_id,
                                    'journal_date' => now(),
                                    'reference'    => $invoice->invoice_number,
                                    'source'       => 'Purchase Invoice',
                                    'memo'         => "Tagihan Pembelian - {$record->vendor->name} (PO: {$record->po_number})",
                                ]);

                                // DEBIT: Hutang Belum Difakturkan (Hutang Sementara Berkurang)
                                $journal->lines()->create([
                                    'account_id' => $unbilledAccount->id,
                                    'direction'  => 'debit',
                                    'amount'     => $invoice->grand_total,
                                    'note'       => 'Pembalikan Hutang Belum Difakturkan',
                                ]);

                                // KREDIT: Hutang Usaha (Hutang Riil Muncul)
                                $journal->lines()->create([
                                    'account_id' => $apAccount->id,
                                    'direction'  => 'credit',
                                    'amount'     => $invoice->grand_total,
                                    'note'       => 'Pengakuan Hutang Dagang Vendor',
                                ]);
                            } else {
                                Notification::make()
                                    ->title('Jurnal Gagal Dibuat')
                                    ->body("Akun dengan kode {$unbilledCode} atau {$apCode} tidak ditemukan di database.")
                                    ->danger()
                                    ->persistent()
                                    ->send();
                                
                                throw new \Exception("Akun tidak ditemukan.");
                            }

                            $record->update(['status' => 'Billed']); 
                            $record->refresh();

                            Notification::make()->title('Tagihan & Jurnal Berhasil Direkam')->success()->send();

                            return redirect()->to(PurchaseInvoiceResource::getUrl('edit', ['record' => $invoice->id]));
                        });
                    })
                    ->hidden(fn (PurchaseOrder $record) => PurchaseInvoice::where('purchase_order_id', $record->id)->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInputVendorBills::route('/'),
        ];
    }
}