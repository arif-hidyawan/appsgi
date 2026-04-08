<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliverGoodsResource\Pages;
use App\Models\SalesOrder;
use App\Models\DeliveryOrder;
use App\Models\ProductStock;
use App\Models\Account; // Tambahkan ini
use App\Models\Journal; // Tambahkan ini
use App\Filament\Resources\DeliveryOrderResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DeliverGoodsResource extends Resource
{
    protected static ?string $model = SalesOrder::class;

    protected static ?string $slug = 'inventory/kirim-barang';

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Kirim Barang (SO)';
    protected static ?string $modelLabel = 'Pengiriman Barang';
    protected static ?int $navigationSort = 8;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('status', ['Siap Kirim', 'Processed']);
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
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->date('d M Y')
                    ->label('Tgl Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer Tujuan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('items.custom_name')
                    ->label('Barang yg Dikirim')
                    ->formatStateUsing(function ($state, $record) {
                        $items = $record->items; 
                        if ($items->isEmpty()) return '-';

                        return $items->map(function ($item) {
                            $displayName = $item->custom_name ?? $item->product->name;
                            return "{$displayName} ({$item->qty} unit)";
                        })->implode('<br>');
                    })
                    ->html()
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Processed' => 'Kirim Sebagian',
                        'Siap Kirim' => 'Menunggu Pengiriman',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Processed' => 'warning',
                        'Siap Kirim' => 'success',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Filter Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('create_delivery')
                    ->label('Proses Kirim (DO)')
                    ->icon('heroicon-o-paper-airplane') 
                    ->color('primary')
                    ->button()
                    ->modalHeading('Buat Surat Jalan (Delivery Order)')
                    ->modalDescription('Masukkan jumlah fisik barang yang naik ke kendaraan saat ini.')
                    ->form(function (SalesOrder $record) {
                        $formSchema = [];
                        
                        foreach ($record->items as $item) {
                            $alreadyDelivered = \App\Models\DeliveryOrderItem::whereHas('deliveryOrder', function ($q) use ($record) {
                                $q->where('sales_order_id', $record->id);
                            })->where('product_id', $item->product_id)->sum('qty_delivered');

                            $remainingQty = $item->qty - $alreadyDelivered;

                            if ($remainingQty <= 0) continue;

                            $currentStock = \App\Models\ProductStock::where('company_id', $record->company_id)
                                ->where('product_id', $item->product_id)
                                ->sum('quantity');

                            $suggestedQty = min($remainingQty, $currentStock);
                            $displayName = $item->custom_name ?? $item->product->name;

                            $formSchema[] = Forms\Components\Group::make([
                                Forms\Components\TextInput::make("items.{$item->product_id}")
                                    ->label($displayName)
                                    ->helperText("Sisa Order: {$remainingQty} | Stok Fisik: {$currentStock}")
                                    ->numeric()
                                    ->default($suggestedQty) 
                                    ->minValue(0)
                                    ->maxValue($remainingQty) 
                                    ->required(),
                                
                                Forms\Components\Hidden::make("cost_price.{$item->product_id}")
                                    ->default($item->product->cost_price ?? 0), 
                            ]);
                        }

                        if (empty($formSchema)) {
                            return [
                                Forms\Components\Placeholder::make('info')
                                    ->content('Semua barang dalam Sales Order ini sudah dikirim sepenuhnya.')
                                    ->extraAttributes(['class' => 'text-success-600 font-bold']),
                            ];
                        }

                        return $formSchema;
                    })
                    ->action(function (SalesOrder $record, array $data) {
                        $itemsToDeliver = collect($data['items'] ?? [])->filter(fn($qty) => $qty > 0);

                        if ($itemsToDeliver->isEmpty()) {
                            Notification::make()->title('Gagal')->body('Tidak ada jumlah barang yang diinput.')->warning()->send();
                            return;
                        }

                        $do = DB::transaction(function () use ($record, $itemsToDeliver, $data) {
                            $do = DeliveryOrder::create([
                                'do_number' => 'DO-' . now()->format('ymd') . '-' . rand(1000, 9999),
                                'date' => now(), 
                                'sales_order_id' => $record->id,
                                'customer_id' => $record->customer_id,
                                'company_id' => $record->company_id,
                                'vehicle_number' => '-', 
                                'status' => 'Draft', 
                            ]);

                            $totalCostValue = 0;

                            foreach ($itemsToDeliver as $productId => $qty) {
                                $soItem = $record->items->where('product_id', $productId)->first();
                                
                                $do->items()->create([
                                    'product_id' => $productId,
                                    'qty_ordered' => $soItem ? $soItem->qty : $qty, 
                                    'qty_delivered' => $qty, 
                                ]);

                                // Hitung total HPP berdasarkan COGS produk
                                $costPrice = $data['cost_price'][$productId] ?? 0;
                                $totalCostValue += ($costPrice * $qty);

                                // Update Stok Fisik
                                ProductStock::where('company_id', $record->company_id)
                                    ->where('product_id', $productId)
                                    ->decrement('quantity', $qty);
                            }

                            // --- JURNAL OTOMATIS HPP (SESUAI DATABASE APPSGI) ---
                            $hppAcc = Account::where('company_id', $record->company_id)->where('code', '5101')->first(); // Harga Pokok Penjualan
                            $invAcc = Account::where('company_id', $record->company_id)->where('code', '1105.001')->first(); // Persediaan Barang Dagang

                            if ($hppAcc && $invAcc && $totalCostValue > 0) {
                                $journal = Journal::create([
                                    'company_id'   => $record->company_id,
                                    'journal_date' => now(),
                                    'reference'    => $do->do_number,
                                    'source'       => 'Delivery Order',
                                    'memo'         => "Jurnal HPP atas pengiriman DO: {$do->do_number} (Ref SO: {$record->so_number})",
                                ]);

                                // DEBIT: HPP
                                $journal->lines()->create([
                                    'account_id' => $hppAcc->id,
                                    'direction'  => 'debit',
                                    'amount'     => $totalCostValue,
                                    'note'       => 'Beban Pokok Penjualan',
                                ]);

                                // KREDIT: Persediaan
                                $journal->lines()->create([
                                    'account_id' => $invAcc->id,
                                    'direction'  => 'credit',
                                    'amount'     => $totalCostValue,
                                    'note'       => 'Pengurangan Nilai Stok',
                                ]);
                            }

                            $totalOrdered = $record->items->sum('qty');
                            $totalDeliveredAllTime = \App\Models\DeliveryOrderItem::whereHas('deliveryOrder', function ($q) use ($record) {
                                $q->where('sales_order_id', $record->id);
                            })->sum('qty_delivered');

                            if ($totalDeliveredAllTime >= $totalOrdered) {
                                $record->update(['status' => 'Completed']);
                            } else {
                                $record->update(['status' => 'Processed']); 
                            }

                            Notification::make()->title('Pengiriman Berhasil Direkam')->success()->send();
                            return $do;
                        });

                        if ($do) return redirect()->to(DeliveryOrderResource::getUrl('edit', ['record' => $do->id]));
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageDeliverGoods::route('/')];
    }
}