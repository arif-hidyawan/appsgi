<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProspekResource\Pages;
use App\Models\Prospek;
use App\Models\SalesOrder;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use App\Filament\Concerns\HasPermissionPrefix;

class ProspekResource extends Resource
{
    protected static ?string $model = Prospek::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Prospek';
    protected static ?int $navigationSort = 19;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'prospek';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Data Kunjungan')
                            ->schema([
                                // 1. SALESMAN (Simpan ke sales_id)
                                Forms\Components\Hidden::make('sales_id')
                                    ->default(auth()->id()),
                                
                                // 2. ID HELPER
                                Forms\Components\Hidden::make('record_id')
                                    ->default(fn ($record) => $record?->id)
                                    ->dehydrated(false), 

                                // 3. CUSTOMER
                                Forms\Components\Select::make('customer_id')
                                    ->label('Customer')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabledOn('edit') 
                                    ->live() 
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if (! $state) {
                                            $set('last_order_date', null);
                                            $set('last_order_amount', 0);
                                            $set('previous_visit_date', '-');
                                            return;
                                        }

                                        $lastOrder = SalesOrder::where('customer_id', $state)
                                            ->orderBy('date', 'desc')
                                            ->orderBy('id', 'desc')
                                            ->first();

                                        if ($lastOrder) {
                                            $tgl = $lastOrder->date ? $lastOrder->date->format('Y-m-d') : null;
                                            $set('last_order_date', $tgl);
                                            $set('last_order_amount', $lastOrder->grand_total);
                                            
                                            Notification::make()
                                                ->title('Data Order Ditemukan!')
                                                ->body('Nominal: Rp ' . number_format($lastOrder->grand_total))
                                                ->success()
                                                ->send();
                                        } else {
                                            $set('last_order_date', null);
                                            $set('last_order_amount', 0);
                                            Notification::make()->title('Customer ini belum ada Order')->warning()->send();
                                        }

                                        $currentId = $get('record_id');
                                        
                                        $lastProspek = Prospek::where('customer_id', $state)
                                            ->when($currentId, fn($q) => $q->where('id', '!=', $currentId)) 
                                            ->orderBy('last_visit_date', 'desc')
                                            ->first();

                                        if ($lastProspek && $lastProspek->last_visit_date) {
                                            $set('previous_visit_date', $lastProspek->last_visit_date->translatedFormat('d F Y'));
                                        } else {
                                            $set('previous_visit_date', 'Belum pernah dikunjungi');
                                        }
                                    })
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->required(),
                                        Forms\Components\TextInput::make('email')->email(),
                                    ])
                                    ->columnSpan(2),

                                // 4. RIWAYAT KUNJUNGAN
                                Forms\Components\TextInput::make('previous_visit_date')
                                    ->label('Riwayat Kunjungan Terakhir')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('-')
                                    ->formatStateUsing(function ($record, Get $get) {
                                        $customerId = $get('customer_id') ?? $record?->customer_id;
                                        if (! $customerId) return '-';

                                        $currentId = $record?->id;
                                        $lastProspek = Prospek::where('customer_id', $customerId)
                                            ->when($currentId, fn($q) => $q->where('id', '!=', $currentId))
                                            ->orderBy('last_visit_date', 'desc')
                                            ->first();

                                        return $lastProspek && $lastProspek->last_visit_date 
                                            ? $lastProspek->last_visit_date->translatedFormat('d F Y') 
                                            : 'Belum pernah dikunjungi';
                                    }),

                                // 5. TGL KUNJUNGAN SAAT INI
                                Forms\Components\DatePicker::make('last_visit_date')
                                    ->label('Tanggal Kunjungan (Baru)')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d F Y')
                                    ->default(now()),

                                // 6. STATUS
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'Hot' => 'Hot', 'Cold' => 'Cold', 
                                        'Quotation' => 'Quotation', 'RFQ' => 'RFQ'
                                    ])
                                    ->required()
                                    ->columnSpanFull(),
                            ])->columns(2),

                        // 7. HISTORY ORDER
                        Forms\Components\Section::make('History Order (Otomatis)')
                            ->schema([
                                Forms\Components\DatePicker::make('last_order_date')
                                    ->label('Tgl Order Terakhir')
                                    ->native(false)
                                    ->displayFormat('d F Y')
                                    ->readOnly()
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('last_order_amount')
                                    ->label('Nominal Order')
                                    ->prefix('Rp')
                                    ->numeric()
                                    ->readOnly()
                                    ->disabled()
                                    ->dehydrated(),
                            ])->columns(2),

                    ])->columnSpan(2),

                // 8. FOTO
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\FileUpload::make('photo')
                            ->directory('prospek-photos')
                            ->imageEditor(),
                    ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sales.name') // Ubah dari user.name
                    ->label('Salesman')
                    ->sortable()
                    ->searchable()
                    ->description(fn (Prospek $record) => $record->sales->email ?? '-'),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Hot' => 'danger',
                        'Cold' => 'info',
                        'Quotation' => 'warning',
                        'RFQ' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('last_visit_date')
                    ->label('Tanggal Kunjungan')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('lead_time')
                    ->label('Lead Time')
                    ->badge()
                    ->icon('heroicon-m-clock')
                    ->state(function (Prospek $record) {
                        if (! $record->last_visit_date) return '-';
                        $days = (int) now()->diffInDays($record->last_visit_date);
                        return $days . ' Hari'; 
                    })
                    ->color(fn (string $state): string => (int) $state > 30 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('last_order_amount')
                    ->label('Last Order')
                    ->money('IDR')
                    ->placeholder('Belum ada order')
                    ->sortable(),

                Tables\Columns\ImageColumn::make('photo')
                    ->label('Foto')
                    ->circular(),
            ])
            ->defaultSort('last_visit_date', 'desc')
            
            ->filters([
                // 1. FILTER SALESMAN
                Tables\Filters\SelectFilter::make('sales_id') // Ubah dari user_id
                    ->label('Salesman')
                    ->options(function () {
                        return User::whereIn('id', Prospek::distinct()->pluck('sales_id'))
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload(),

                // 2. FILTER CUSTOMER
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),

                // 3. FILTER STATUS
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Hot' => 'Hot',
                        'Cold' => 'Cold',
                        'Quotation' => 'Quotation',
                        'RFQ' => 'RFQ',
                    ]),
            ])

            ->actions([
                Tables\Actions\Action::make('history')
                    ->label('Riwayat Sales')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->modalHeading(fn (Prospek $record) => "Riwayat {$record->sales->name} ke {$record->customer->name}")
                    ->modalSubmitAction(false) 
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function (Prospek $record) {
                        $histories = Prospek::where('customer_id', $record->customer_id)
                            ->where('sales_id', $record->sales_id) // Ubah sales_id
                            ->orderBy('last_visit_date', 'desc')
                            ->get();

                        return view('filament.components.prospek-history', ['histories' => $histories]);
                    }),

                Tables\Actions\EditAction::make()
                    ->visible(fn (Prospek $record) => $record->sales_id === auth()->id()),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Prospek $record) => $record->sales_id === auth()->id()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(function ($record) {
                                if ($record->sales_id === auth()->id()) {
                                    $record->delete();
                                }
                            });
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProspeks::route('/'),
            'create' => Pages\CreateProspek::route('/create'),
            'edit' => Pages\EditProspek::route('/{record}/edit'),
        ];
    }
}