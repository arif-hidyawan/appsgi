<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\Company; // Pastikan model Company diimport
use App\Models\JournalLine;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Filament\Forms\Get;
use Filament\Forms\Set;

class BukuBesar extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationGroup = 'Accounting';
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Buku Besar';
    protected static ?string $title = 'Buku Besar';
    protected static ?int $navigationSort = 17;
    
    protected static string $view = 'filament.pages.buku-besar';

    // State
    public ?int $company_id = null; 
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?int $account_id = null;

    // Hasil Report
    public float $opening_balance = 0.0;
    public float $total_debit = 0.0;
    public float $total_credit = 0.0;
    public string $normal_pos = 'debit';
    public ?Account $selectedAccount = null;

    public array $ledger_rows = [];

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->toDateString();
        $this->end_date = now()->toDateString();
        
        // FIX: Ambil ID Perusahaan dengan aman (bisa null)
        $this->company_id = filament()->getTenant()?->id;

        // Jika Company ID sudah ada (misal login sbg tenant), coba ambil akun pertama
        if ($this->company_id) {
            $firstAccount = Account::where('company_id', $this->company_id)
                ->where('type', 'D')
                ->orderBy('code')
                ->first();
                
            $this->account_id = $firstAccount?->id;

            if ($this->account_id) {
                $this->process();
            }
        }
        
        // Isi form awal
        $this->form->fill([
            'company_id' => $this->company_id,
            'account_id' => $this->account_id,
            'start_date' => $this->start_date,
            'end_date'   => $this->end_date,
        ]);
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Filter Laporan')
                ->schema([
                    Forms\Components\Grid::make(4)->schema([
                        // 1. FILTER PERUSAHAAN (Wajib ada untuk Super Admin)
                        Forms\Components\Select::make('company_id')
                            ->label('Perusahaan')
                            ->options(Company::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->default(fn () => filament()->getTenant()?->id)
                            ->disabled(fn () => filament()->getTenant() !== null)
                            ->dehydrated() // Tetap kirim value meski disabled
                            ->live() // Agar dropdown akun di bawah ikut refresh
                            ->afterStateUpdated(fn (Set $set) => $set('account_id', null)) // Reset akun jika ganti PT
                            ->required(),

                        // 2. FILTER AKUN DENGAN TREE VIEW (OPTGROUP)
                        Forms\Components\Select::make('account_id')
                            ->label('Pilih Akun')
                            ->options(function (Get $get) {
                                // Ambil ID dari inputan form atau tenant aktif
                                $companyId = $get('company_id') ?? filament()->getTenant()?->id;
                                
                                if (!$companyId) return []; // Kosong jika belum pilih PT

                                // Ambil semua akun detail beserta induknya
                                $accounts = Account::query()
                                    ->where('company_id', $companyId)
                                    ->where('type', 'D')
                                    ->with('parent') // Load relasi parent agar tidak N+1 query
                                    ->orderBy('code')
                                    ->get();

                                $options = [];
                                foreach ($accounts as $account) {
                                    // Tentukan nama Grup (Induk)
                                    $groupName = $account->parent ? "{$account->parent->code} - {$account->parent->name}" : 'Tanpa Induk';
                                    
                                    // Masukkan akun ke dalam array grup tersebut
                                    $options[$groupName][$account->id] = "{$account->code} - {$account->name}";
                                }

                                return $options;
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Dari Tanggal')
                            ->required(),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Sampai Tanggal')
                            ->required(),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('filter')
                                ->label('Tampilkan')
                                ->icon('heroicon-m-funnel')
                                ->action(fn () => $this->process()),
                        ])->columnSpan(1)->alignEnd(),
                    ]),
                ]),
        ]);
    }

    public function process(): void
    {
        $data = $this->form->getState();
        
        // Ambil data dengan fallback yang aman
        $this->company_id = $data['company_id'] ?? filament()->getTenant()?->id;
        $this->account_id = $data['account_id'] ?? null;
        $this->start_date = $data['start_date'] ?? now()->toDateString();
        $this->end_date   = $data['end_date'] ?? now()->toDateString();

        $this->ledger_rows = [];
        $this->total_debit = 0.0;
        $this->total_credit = 0.0;
        $this->opening_balance = 0.0;

        // Validasi: Jangan jalan jika data belum lengkap
        if (!$this->company_id || !$this->account_id) {
            return;
        }

        $startDate = Carbon::parse($this->start_date)->startOfDay();
        $endDate   = Carbon::parse($this->end_date)->endOfDay();

        // 1. Ambil Info Akun
        $this->selectedAccount = Account::find($this->account_id);
        if (!$this->selectedAccount) return;

        $this->normal_pos = match ($this->selectedAccount->nature) {
            'asset', 'expense', 'cogs', 'other_expense' => 'debit',
            default => 'credit',
        };

        // 2. Hitung SALDO AWAL (Query ke Journals & Lines)
        $openingQuery = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->where('j.company_id', $this->company_id) // Filter Company
            ->where('jl.account_id', $this->account_id)
            ->where('j.journal_date', '<', $startDate->toDateString())
            ->selectRaw("
                COALESCE(SUM(CASE WHEN jl.direction='debit' THEN jl.amount ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN jl.direction='credit' THEN jl.amount ELSE 0 END), 0) as total_credit
            ")
            ->first();

        $pastDebit  = (float) ($openingQuery->total_debit ?? 0);
        $pastCredit = (float) ($openingQuery->total_credit ?? 0);

        if ($this->normal_pos === 'debit') {
            $this->opening_balance = $pastDebit - $pastCredit;
        } else {
            $this->opening_balance = $pastCredit - $pastDebit;
        }

        // Add Opening Row
        $this->ledger_rows[] = [
            'date'        => $startDate,
            'ref'         => '',
            'description' => 'Saldo Awal',
            'debit'       => 0.0,
            'credit'      => 0.0,
            'balance'     => $this->opening_balance,
            'is_opening'  => true,
        ];

        // 3. Ambil Transaksi PERIODE INI
        $transactions = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->where('j.company_id', $this->company_id) // Filter Company
            ->where('jl.account_id', $this->account_id)
            ->whereBetween('j.journal_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('j.journal_date')
            ->orderBy('j.created_at')
            ->orderBy('jl.id')
            ->select([
                'j.journal_date',
                'j.reference',
                'j.memo',
                'jl.note',
                'jl.direction',
                'jl.amount',
                'j.created_at'
            ])
            ->get();

        // 4. Hitung Running Balance
        $runningBalance = $this->opening_balance;

        foreach ($transactions as $trx) {
            $debit  = ($trx->direction === 'debit') ? (float) $trx->amount : 0.0;
            $credit = ($trx->direction === 'credit') ? (float) $trx->amount : 0.0;

            if ($this->normal_pos === 'debit') {
                $runningBalance += ($debit - $credit);
            } else {
                $runningBalance += ($credit - $debit);
            }

            $this->total_debit  += $debit;
            $this->total_credit += $credit;

            $this->ledger_rows[] = [
                'date'        => Carbon::parse($trx->created_at ?? $trx->journal_date),
                'ref'         => $trx->reference ?? '-',
                'description' => $trx->note ?? $trx->memo ?? '-',
                'debit'       => $debit,
                'credit'      => $credit,
                'balance'     => $runningBalance,
                'is_opening'  => false,
            ];
        }
    }

    public function fmt(float $n): string
    {
        return number_format($n, 2, ',', '.');
    }
}