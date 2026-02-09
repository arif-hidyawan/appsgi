<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuotationResource\Pages;
use App\Filament\Resources\QuotationResource\RelationManagers;
use App\Models\Quotation;
use App\Filament\Resources\RfqResource;
use App\Filament\Resources\SalesOrderResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use App\Filament\Concerns\HasPermissionPrefix;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $modelLabel = 'Quotation';
    protected static ?int $navigationSort = 2;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'quotation';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Info Penawaran')
                            ->schema([
                                Forms\Components\TextInput::make('quotation_number')
                                    ->label('No. Quotation')
                                    ->default('QT-' . now()->format('Ymd') . '-' . rand(100, 999))
                                    ->required()
                                    ->disabledOn('edit')
                                    ->dehydrated()
                                    ->readOnly(),

                                // --- FIX: HAPUS dehydrated() DISINI ---
                                Forms\Components\Select::make('rfqs')
                                    ->label('Referensi RFQ(s)')
                                    ->relationship('rfqs', 'rfq_number') // Relasi Many-to-Many
                                    ->multiple() // SUPPORT BANYAK RFQ
                                    ->searchable()
                                    ->preload()
                                    ->disabledOn('edit') 
                                    // ->dehydrated() <--- INI PENYEBAB ERRORNYA, SUDAH DIHAPUS
                                    ->helperText('Bisa berasal dari satu atau lebih RFQ (Merge)'),

                                Forms\Components\DatePicker::make('date')
                                    ->label('Tanggal')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\Select::make('customer_id')
                                    ->label('Customer')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->disabledOn('edit')
                                    ->dehydrated()
                                    ->required(),

                                Forms\Components\Select::make('customer_contact_id')
                                    ->label('PIC / Kontak')
                                    ->relationship('contact', 'pic_name')
                                    ->options(function (Forms\Get $get) {
                                        $customerId = $get('customer_id');
                                        if (! $customerId) return [];
                                        
                                        return \App\Models\CustomerContact::where('customer_id', $customerId)
                                            ->get()
                                            ->mapWithKeys(function ($contact) {
                                                $label = $contact->pic_name;
                                                if (!empty($contact->phone)) {
                                                    $label .= ' - ' . $contact->phone;
                                                }
                                                return [$contact->id => $label];
                                            });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabledOn('edit')
                                    ->dehydrated()
                                    ->placeholder(fn (Forms\Get $get) => empty($get('customer_id')) ? 'Pilih Customer Dulu' : 'Pilih PIC'),
                               
                                Forms\Components\Select::make('sales_id')
                                    ->label('Sales Staff')
                                    ->relationship('sales', 'name')
                                    ->searchable()
                                    ->disabledOn('edit')
                                    ->dehydrated()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set) {
                                        $set('company_id', null);
                                    }),

                                Forms\Components\Select::make('company_id')
                                    ->label('Perusahaan')
                                    ->helperText('Entitas perusahaan untuk penawaran ini.')
                                    ->options(function (Forms\Get $get) {
                                        $salesId = $get('sales_id');
                                        if (! $salesId) return []; 

                                        return \App\Models\Company::whereHas('users', function ($query) use ($salesId) {
                                            $query->where('users.id', $salesId);
                                        })->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabledOn('edit')
                                    ->dehydrated()
                                    ->placeholder(fn (Forms\Get $get) => empty($get('sales_id')) ? 'Pilih Sales Dulu' : 'Pilih Perusahaan'),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'Draft' => 'Draft',
                                        'Sent' => 'Dikirim ke Customer',
                                        'Partial' => 'Diproses PO Sebagian',
                                        'Accepted' => 'Diproses PO Semua',
                                        'Rejected' => 'Ditolak',
                                    ])
                                    ->default('Draft')
                                    ->required()
                                    ->native(false)
                                    ->disabledOn('edit')
                                    ->dehydrated(),

                                // --- TERMIN PEMBAYARAN ---
                                Forms\Components\TextInput::make('payment_terms')
    ->label('Termin Pembayaran')
    ->placeholder('Pilih atau ketik manual (Contoh: Net 30)')
    ->datalist([
        'Cash',
        'COD',
        'CBD (Cash Before Delivery)',
        'Net 7',
        'Net 14',
        'Net 30',
        'Net 45',
        'Net 60',
        'DP 50% - Pelunasan Saat Barang Siap',
    ])
    ->default('Net 30')
    ->required()
    //->disabledOn('edit') // Sesuaikan jika ingin bisa diedit
    ->dehydrated(),

                                Forms\Components\Textarea::make('rejection_reason')
                                    ->label('Alasan Penolakan')
                                    ->rows(3)
                                    ->readOnly()
                                    ->visible(fn ($record) => $record?->status === 'Rejected')
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan Tambahan')
                            ->columnSpanFull(),

                        Forms\Components\Section::make('Audit Trail')
                            ->description('Informasi waktu pembuatan dan perubahan data')
                            ->collapsed()
                            ->compact()
                            ->schema([
                                Forms\Components\Group::make([
                                    Forms\Components\Placeholder::make('created_by_name')
                                        ->label('Dibuat Oleh')
                                        ->content(fn ($record) => $record?->creator?->name ?? '-'),
                                    
                                    Forms\Components\Placeholder::make('created_at')
                                        ->label('Waktu Dibuat')
                                        ->content(fn ($record) => $record?->created_at?->format('d M Y H:i') ?? '-'),
                                ])->columns(2),

                                Forms\Components\Group::make([
                                    Forms\Components\Placeholder::make('updated_by_name')
                                        ->label('Diubah Oleh Terakhir')
                                        ->content(fn ($record) => $record?->updater?->name ?? '-'),
                                    
                                    Forms\Components\Placeholder::make('updated_at')
                                        ->label('Waktu Perubahan')
                                        ->content(fn ($record) => $record?->updated_at?->format('d M Y H:i') ?? '-'),
                                ])->columns(2),
                            ])
                            ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord),
                    
                    ])
                    ->columnSpanFull()
                    ->disabled(fn (?Quotation $record) => $record && in_array($record->status, ['Accepted', 'Rejected'])),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quotation_number')
                    ->label('No. QT')
                    ->searchable()
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('contact.pic_name')
                    ->label('PIC Customer')
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->icon('heroicon-m-building-office-2')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('rfqs.rfq_number')
                    ->label('Ref RFQ')
                    ->badge()
                    ->color('gray')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn ($record) => $record->rfqs->first() ? RfqResource::getUrl('edit', ['record' => $record->rfqs->first()->id]) : null)
                    ->openUrlInNewTab()
                    ->placeholder('-'),

                    Tables\Columns\TextColumn::make('items.product.name')
                    ->label('Item Produk')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->badge()
                    ->color('gray')
                    // Logic pencarian khusus untuk relasi bersarang (Nested Relationship)
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('items.product', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->toggleable(), // Agar kolom bisa disembunyikan jika tabel terlalu penuh

                    Tables\Columns\TextColumn::make('payment_terms')
                    ->label('Termin')
                    ->badge()
                    ->color('info') // Tetap pakai badge biru agar rapi
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('grand_total')
                    ->money('IDR')
                    ->label('Total'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Draft' => 'Draft',
                        'Sent' => 'Dikirim',
                        'Partial' => 'Diproses PO Sebagian',
                        'Accepted' => 'Diproses PO Semua',
                        'Rejected' => 'Ditolak',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'Sent' => 'warning',
                        'Partial' => 'info',
                        'Accepted' => 'success',
                        'Rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('updater.name')
                    ->label('Diubah Oleh')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Draft' => 'Draft',
                        'Sent' => 'Dikirim',
                        'Partial' => 'Diproses PO Sebagian',
                        'Accepted' => 'Diproses PO Semua',
                        'Rejected' => 'Ditolak',
                    ]),
                
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([

                    Tables\Actions\ReplicateAction::make()
                        ->label('Duplikat Quotation')
                        ->icon('heroicon-m-square-2-stack')
                        ->color('success')
                        ->modalHeading('Duplikat Penawaran')
                        ->modalDescription('Ini akan membuat salinan penawaran baru. Referensi RFQ dan item produk akan tetap dipertahankan.')
                        ->modalSubmitActionLabel('Ya, Duplikat')
                        ->beforeReplicaSaved(function (Quotation $replica) {
                            $replica->quotation_number = 'QT-' . now()->format('Ymd') . '-' . rand(100, 999);
                            $replica->date = now();
                            $replica->status = 'Draft';
                            $replica->grand_total = 0;
                        })
                        ->after(function (Quotation $original, Quotation $replica) {
                            foreach ($original->items as $item) {
                                $newItem = $item->replicate();
                                $newItem->quotation_id = $replica->id;
                                $newItem->save();
                            }

                            // Re-attach RFQs
                            $replica->rfqs()->attach($original->rfqs->pluck('id'));

                            $total = $replica->items()->sum('subtotal');
                            $replica->update(['grand_total' => $total]);

                            Notification::make()
                                ->title('Quotation Berhasil Diduplikasi')
                                ->body("Nomor baru: {$replica->quotation_number}")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('send_to_customer')
                        ->label(fn (Quotation $record) => $record->status === 'Sent' ? 'Kirim Ulang' : 'Kirim ke Customer')
                        ->icon(fn (Quotation $record) => $record->status === 'Sent' ? 'heroicon-o-arrow-path' : 'heroicon-o-paper-airplane')
                        ->color('warning')
                        ->visible(fn (Quotation $record) => in_array($record->status, ['Draft', 'Sent']))
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Pengiriman')
                        ->modalDescription('Apakah Anda yakin ingin mengirim dokumen ini ke customer? Status akan berubah menjadi "Dikirim".')
                        ->modalSubmitActionLabel('Ya, Kirim')
                        ->action(function (Quotation $record) {
                            $record->update(['status' => 'Sent']);
                            Notification::make()->title('Berhasil Dikirim')->success()->send();
                        }),

                    Tables\Actions\Action::make('create_so')
                        ->label('Jadikan Sales Order (SO)')
                        ->icon('heroicon-o-shopping-bag')
                        ->color('primary')
                        ->visible(fn (Quotation $record) => !in_array($record->status, ['Accepted', 'Rejected', 'Draft']))
                        ->form(function (Quotation $record) {
                            $quoteItems = $record->items;
                            $options = [];
                            
                            foreach ($quoteItems as $item) {
                                // Hitung total qty item ini yang SUDAH di order di SO manapun yg terhubung ke quote ini
                                $alreadyOrdered = \App\Models\SalesOrderItem::whereHas('salesOrder', function($q) use ($record) {
                                    $q->whereHas('quotations', fn($quot) => $quot->where('quotations.id', $record->id));
                                })->where('product_id', $item->product_id)->sum('qty');

                                $remaining = $item->qty - $alreadyOrdered;

                                if ($remaining > 0) {
                                    $label = "{$item->product->name} (Sisa: {$remaining} dari {$item->qty})";
                                    $options[$item->id] = $label;
                                }
                            }

                            if (empty($options)) {
                                return [
                                    Forms\Components\Placeholder::make('info')
                                        ->content('Semua item dalam penawaran ini sudah dibuatkan Sales Order.')
                                        ->extraAttributes(['class' => 'text-danger-600 font-bold']),
                                ];
                            }

                            return [
                                Forms\Components\TextInput::make('customer_po_number')
                                    ->label('Nomor PO Customer')
                                    ->required()
                                    ->placeholder('Contoh: PO-001'),

                                Forms\Components\CheckboxList::make('selected_item_ids')
                                    ->label('Pilih Item untuk SO ini')
                                    ->options($options)
                                    ->required()
                                    ->columns(1),
                            ];
                        })
                        ->action(function (Quotation $record, array $data) {
                            if (!isset($data['selected_item_ids']) || empty($data['selected_item_ids'])) {
                                Notification::make()->title('Tidak ada item yang bisa diproses')->warning()->send();
                                return;
                            }

                            // 1. Buat Header SO (Tanpa quotation_id)
                            $so = \App\Models\SalesOrder::create([
                                'so_number' => 'SO-' . now()->format('ymd') . '-' . rand(1000, 9999),
                                'date' => now(),
                                'customer_id' => $record->customer_id,
                                'sales_id' => $record->sales_id,
                                'company_id' => $record->company_id,
                                'customer_po_number' => $data['customer_po_number'],
                                'status' => 'New',
                                'payment_terms' => $record->payment_terms,
                                'grand_total' => 0,
                            ]);

                            // 2. Attach Quotation via Pivot
                            $so->quotations()->attach($record->id);

                            $totalSO = 0;
                            $selectedIds = $data['selected_item_ids'];

                            foreach ($selectedIds as $qItemId) {
                                $qItem = $record->items()->find($qItemId);
                                
                                if ($qItem) {
                                    // Cek sisa qty lagi untuk safety
                                    $alreadyOrdered = \App\Models\SalesOrderItem::whereHas('salesOrder', function($q) use ($record) {
                                        $q->whereHas('quotations', fn($quot) => $quot->where('quotations.id', $record->id));
                                    })->where('product_id', $qItem->product_id)->sum('qty');

                                    $qtyToOrder = $qItem->qty - $alreadyOrdered;

                                    if ($qtyToOrder > 0) {
                                        $subtotal = $qtyToOrder * $qItem->unit_price;
                                        $so->items()->create([
                                            'product_id' => $qItem->product_id,
                                            'vendor_id'  => $qItem->vendor_id,
                                            'cost_price' => $qItem->cost_price, 
                                            'notes'      => $qItem->notes,
                                            'qty'        => $qtyToOrder,
                                            'lead_time'  => $qItem->lead_time,
                                            'unit_price' => $qItem->unit_price,
                                            'subtotal'   => $subtotal,
                                        ]);
                                        $totalSO += $subtotal;
                                    }
                                }
                            }

                            $so->update(['grand_total' => $totalSO]);

                            // 3. Update Status Quotation
                            $totalQuoteQty = $record->items()->sum('qty');
                            $totalOrderedQty = \App\Models\SalesOrderItem::whereHas('salesOrder', function($q) use ($record) {
                                $q->whereHas('quotations', fn($quot) => $quot->where('quotations.id', $record->id));
                            })->sum('qty');

                            if ($totalOrderedQty >= $totalQuoteQty) {
                                $record->update(['status' => 'Accepted']);
                            } else {
                                $record->update(['status' => 'Partial']);
                            }

                            Notification::make()->title('Sales Order Berhasil Dibuat')->success()->send();

                            return redirect()->to(SalesOrderResource::getUrl('edit', ['record' => $so->id]));
                        }),

                    Tables\Actions\Action::make('print')
                        ->label('Cetak PDF')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->url(fn (Quotation $record) => route('print.quotation', $record))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('traceability')
                        ->label('Alur Dokumen')
                        ->icon('heroicon-o-arrows-right-left')
                        ->color('info')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->modalContent(function (Quotation $record) {
                            return view('filament.components.document-traceability', ['record' => $record]);
                        }),

                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('reject_quotation')
                        ->label('Penawaran Ditolak')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Quotation $record) => in_array($record->status, ['Draft', 'Sent']))
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Penolakan')
                        ->modalSubmitActionLabel('Simpan & Tolak')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Alasan Ditolak')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (Quotation $record, array $data) {
                            $record->update([
                                'status' => 'Rejected',
                                'rejection_reason' => $data['rejection_reason'],
                            ]);
                            Notification::make()->title('Penawaran Ditolak')->danger()->send();
                        }),

                ])
                ->label('Aksi')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('info')
                ->tooltip('Menu Pilihan'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    
                    // --- ACTION MERGE PENAWARAN (PIVOT SO) ---
                    Tables\Actions\BulkAction::make('merge_to_so')
                        ->label('Gabungkan ke 1 Sales Order')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Gabungkan Penawaran')
                        ->modalDescription('Beberapa penawaran terpilih akan digabungkan menjadi satu Sales Order baru. Pastikan Customer dan Perusahaan sama.')
                        ->form([
                            Forms\Components\TextInput::make('customer_po_number')
                                ->label('Nomor PO Customer')
                                ->required()
                                ->placeholder('Contoh: PO-MERGE-001'),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $firstRecord = $records->first();

                            if (!$firstRecord) {
                                Notification::make()->title('Gagal')->body('Tidak ada data terpilih.')->danger()->send();
                                return;
                            }
                            $customerId = $firstRecord->customer_id;
                            $companyId = $firstRecord->company_id;
    
                            foreach ($records as $record) {
                                if ($record->customer_id !== $customerId || $record->company_id !== $companyId) {
                                    Notification::make()->title('Gagal Menggabungkan')->body('Customer/Perusahaan tidak sama.')->danger()->send();
                                    return;
                                }
                                if (in_array($record->status, ['Accepted', 'Rejected'])) {
                                    Notification::make()->title('Gagal')->body("Quotation {$record->quotation_number} sudah diproses/ditolak.")->danger()->send();
                                    return;
                                }
                            }
    
                            // 1. Buat Header SO (Tanpa quotation_id)
                            $so = \App\Models\SalesOrder::create([
                                'so_number' => 'SO-' . now()->format('ymd') . '-' . rand(1000, 9999),
                                'date' => now(),
                                'customer_id' => $customerId,
                                'company_id' => $companyId,
                                'sales_id' => $record->sales_id,
                                'customer_po_number' => $data['customer_po_number'],
                                'status' => 'New',
                                'payment_terms' => $record->payment_terms,
                                'grand_total' => 0,
                                'notes' => 'Gabungan dari Quotation: ' . $records->pluck('quotation_number')->implode(', '),
                            ]);

                            // 2. Attach Quotations ke SO (Pivot)
                            $so->quotations()->attach($records->pluck('id'));
    
                            $totalSO = 0;
    
                            // 3. Loop Item
                            foreach ($records as $quotation) {
                                foreach ($quotation->items as $qItem) {
                                    // Cek sisa qty agar tidak double
                                    $alreadyOrdered = \App\Models\SalesOrderItem::whereHas('salesOrder', function($q) use ($quotation) {
                                        $q->whereHas('quotations', fn($quot) => $quot->where('quotations.id', $quotation->id));
                                    })->where('product_id', $qItem->product_id)->sum('qty');
    
                                    $qtyToOrder = $qItem->qty - $alreadyOrdered;
    
                                    if ($qtyToOrder > 0) {
                                        $subtotal = $qtyToOrder * $qItem->unit_price;
                                        $so->items()->create([
                                            'product_id' => $qItem->product_id,
                                            'vendor_id'  => $qItem->vendor_id,
                                            'cost_price' => $qItem->cost_price,
                                            'notes'      => $qItem->notes,
                                            'qty'        => $qtyToOrder,
                                            'lead_time'  => $qItem->lead_time,
                                            'unit_price' => $qItem->unit_price,
                                            'subtotal'   => $subtotal,
                                        ]);
                                        $totalSO += $subtotal;
                                    }
                                }
                                $quotation->update(['status' => 'Accepted']);
                            }
    
                            $so->update(['grand_total' => $totalSO]);
    
                            Notification::make()->title('Berhasil Gabung SO')->success()->send();
                            return redirect()->to(SalesOrderResource::getUrl('edit', ['record' => $so->id]));
                        }),
    
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
            'index' => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'edit' => Pages\EditQuotation::route('/{record}/edit'),
        ];
    }
}