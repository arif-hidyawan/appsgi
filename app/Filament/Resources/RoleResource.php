<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Role; 
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Concerns\HasPermissionPrefix;
use Spatie\Permission\Models\Permission;

class RoleResource extends \Filament\Resources\Resource
{
    // Ensure this points to the correct Spatie Model
    protected static ?string $model = \Spatie\Permission\Models\Role::class;

    protected static ?string $navigationGroup  = 'Pengaturan';
    protected static ?string $navigationIcon   = 'heroicon-o-lock-closed';
    protected static ?string $navigationLabel  = 'Role & Hak Akses';
    protected static ?string $modelLabel       = 'Role';
    protected static ?string $pluralModelLabel = 'Role';
    protected static ?int    $navigationSort   = 94;

    use HasPermissionPrefix;
    protected static ?string $permissionPrefix = 'role';

    /*
     |--------------------------------------------------------------------------
     | FORM
     |--------------------------------------------------------------------------
     */

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Data Role')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Role')
                        ->required()
                        ->maxLength(50)
                        ->unique(ignoreRecord: true)
                        ->helperText('Contoh: purchasing, sales_manager, owner'),
                ])
                ->columns(2),

            Section::make('Hak Akses (Permission)')
                ->description('Centang fitur apa saja yang boleh diakses oleh role ini.')
                ->schema([
                    CheckboxList::make('permissions')
                        ->label('') // Empty label for layout
                        ->relationship(
                            name: 'permissions',
                            titleAttribute: 'name',
                            modifyQueryUsing: function (Builder $query) {
                                $query->orderByRaw("
                                    -- 1. URUTAN TAMPILAN (Sesuai Sidebar)
                                    FIELD(SUBSTRING_INDEX(name, '.', 1),
                                        'dashboard',
                                        
                                        -- CRM
                                        'birthday_reminder', 'prospek',
                                        
                                        -- FINANCE (Invoice & Bills included per image)
                                        'sales_payment', 'purchase_payment', 'sales_invoice', 'purchase_invoice',
                                        
                                        -- MASTER DATA (Customer & Vendor included per image)
                                        'vendor', 'customer', 'product', 'brand', 'category', 'unit', 'tax',
                                        
                                        -- SALES (RFQ included per request)
                                        'quotation', 'sales_order', 'rfq',
                                        
                                        -- PROCUREMENT
                                        'purchase_order',
                                        
                                        -- INVENTORY (Goods Receive & DO included per image)
                                        'goods_receive', 'delivery_order', 'stock_transfer', 'product_stock', 'warehouse',
                                        
                                        -- PENGATURAN
                                        'company', 'user', 'role', 'backup'
                                    ),
                                    
                                    -- 2. URUTAN ACTION
                                    FIELD(SUBSTRING_INDEX(name, '.', -1), 'view', 'create', 'update', 'delete', 'approve', 'print'),
                                    
                                    -- 3. Fallback
                                    name
                                ");
                            },
                        )
                        ->columns(2)
                        ->gridDirection('row')
                        ->searchable()
                        ->bulkToggleable()
                        ->getOptionLabelFromRecordUsing(function (Permission $permission): string {
                            $name = $permission->name;
                            [$prefix, $action] = array_pad(explode('.', $name, 2), 2, null);

                            // --- MAPPING LABEL (Matching Screenshot & Request) ---
                            $groupLabels = [
                                // Dashboard
                                'dashboard' => 'Dashboard - Utama',

                                // CRM
                                'birthday_reminder' => 'CRM - Birthday & Reminder',
                                'prospek'           => 'CRM - Prospek',

                                // Finance
                                'sales_payment'    => 'Finance - Penerimaan Pembayaran',
                                'purchase_payment' => 'Finance - Pembayaran Ke Vendor',
                                'purchase_invoice' => 'Finance - Tagihan Vendor (Bill)',
                                'sales_invoice'    => 'Finance - Faktur Penjualan (Invoice)',

                                // Master Data
                                'vendor'   => 'Master Data - Vendor',
                                'customer' => 'Master Data - Customer',
                                'product'  => 'Master Data - Item / Produk',
                                'brand'    => 'Master Data - Merek / Brand',
                                'category' => 'Master Data - Kategori Produk',
                                'unit'     => 'Master Data - Satuan (Unit)',
                                'tax'      => 'Master Data - Pajak (Tax)',

                                // Sales
                                'quotation'   => 'Sales - Quotation (Penawaran)',
                                'sales_order' => 'Sales - Sales Order',
                                'rfq'         => 'Sales - RFQ (Request for Quotation)',

                                // Procurement
                                'purchase_order' => 'Procurement - Purchase Order',

                                // Inventory
                                'goods_receive'  => 'Inventory - Penerimaan Barang',
                                'delivery_order' => 'Inventory - Surat Jalan (DO)',
                                'stock_transfer' => 'Inventory - Mutasi Stok',
                                'product_stock'  => 'Inventory - Stok Terkunci / Cek Stok',
                                'warehouse'      => 'Inventory - Gudang (Warehouse)',

                                // Pengaturan
                                'company' => 'Pengaturan - Perusahaan',
                                'user'    => 'Pengaturan - Pengguna',
                                'role'    => 'Pengaturan - Role & Hak Akses',
                                'backup'  => 'Pengaturan - Database Backup',
                            ];

                            $groupLabel = $groupLabels[$prefix] ?? ucfirst(str_replace('_', ' ', $prefix));

                            // Translate Action
                            $actionMap = [
                                'view'    => 'Lihat',
                                'create'  => 'Buat',
                                'update'  => 'Edit',
                                'delete'  => 'Hapus',
                                'approve' => 'Approve',
                                'print'   => 'Cetak',
                                'view_price' => 'Lihat Harga & Vendor',
                            ];
                            $actionLabel = $actionMap[$action] ?? ucfirst($action);

                            // CSS Style for Actions (Using standard Filament/Tailwind colors that definitely work)
                            $colorClass = match ($action) {
                                'view'   => 'color: #64748b;', // Slate-500
                                'create' => 'color: #16a34a; font-weight: 700;', // Green-600
                                'update' => 'color: #2563eb; font-weight: 700;', // Blue-600
                                'delete' => 'color: #dc2626; font-weight: 700;', // Red-600
                                default  => 'color: #374151;', // Gray-700
                            };

                            // HTML Output with INLINE STYLES to guarantee color rendering
                            // Using color: #000000 for the header to ensure it is black
                            return "<div style='display: flex; flex-direction: column; line-height: 1.25;'>
                                        <span style='font-size: 0.75rem; color: #000000; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;'>{$groupLabel}</span>
                                        <span style='font-size: 0.875rem; {$colorClass}'>{$actionLabel}</span>
                                    </div>";
                        })
                        ->allowHtml(), 
                ]),
        ]);
    }

    /*
     |--------------------------------------------------------------------------
     | TABLE
     |--------------------------------------------------------------------------
     */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Role')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Jml Akses')
                    ->badge()
                    ->color('info'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Atur Akses'),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn (\Spatie\Permission\Models\Role $record) => $record->name !== 'owner'),
            ]);
    }

    /*
     |--------------------------------------------------------------------------
     | PAGES
     |--------------------------------------------------------------------------
     */

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit'   => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}