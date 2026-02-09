<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Prospek;
use App\Models\Rfq;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;

class CustomDashboardChart extends Widget
{
    // Pastikan view-nya mengarah ke file yang benar
    protected static string $view = 'filament.widgets.custom-dashboard-chart';
    
    // Agar lebar widget full satu layar
    protected int | string | array $columnSpan = 'full';
    
    // Property Filter (Livewire)
    public $filterPeriod = 'this_month';
    public $filterSales = '';

    protected function getViewData(): array
    {
        // 1. Tentukan Start & End Date
        $startDate = match ($this->filterPeriod) {
            'today' => Carbon::today(),
            'this_week' => Carbon::now()->startOfWeek(),
            'this_month' => Carbon::now()->startOfMonth(),
            'this_year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };
        
        $endDate = match ($this->filterPeriod) {
            'today' => Carbon::tomorrow(),
            'this_week' => Carbon::now()->endOfWeek(),
            'this_month' => Carbon::now()->endOfMonth(),
            'this_year' => Carbon::now()->endOfYear(),
            default => Carbon::now()->endOfMonth(),
        };

        // 2. Helper Query Filter
        $applyFilter = function ($query) use ($startDate, $endDate) {
            return $query->whereBetween('created_at', [$startDate, $endDate])
                ->when($this->filterSales, fn($q) => $q->where('sales_id', $this->filterSales));
        };

        // --- BAGIAN 1: STATS COUNTER ---
        
        // Total Utama
        $totalProspek = $applyFilter(Prospek::query())->count();
        $totalRfq = $applyFilter(Rfq::query())->count();
        $totalQuotation = $applyFilter(Quotation::query())->count();
        $totalSalesOrder = $applyFilter(SalesOrder::query())->count();

        // RFQ Details
        $rfqConverted = $applyFilter(Quotation::query())
            ->whereNotNull('rfq_id')
            ->where('status', '!=', 'Draft')
            ->distinct('rfq_id')
            ->count('rfq_id');
        $rfqConversionRate = $totalRfq > 0 ? ($rfqConverted / $totalRfq) * 100 : 0;

        // Quotation Details
        $quotationSent = $applyFilter(Quotation::query())->where('status', '!=', 'Draft')->count();
        $quotationSentRate = $totalQuotation > 0 ? ($quotationSent / $totalQuotation) * 100 : 0;

        $quotationAccepted = $applyFilter(Quotation::query())->where('status', 'Accepted')->count();
        $quotationAcceptedRate = $totalQuotation > 0 ? ($quotationAccepted / $totalQuotation) * 100 : 0;

        // Sales Order Details
        $salesOrderCompleted = $applyFilter(SalesOrder::query())->where('status', 'Completed')->count();
        $salesOrderCompletedRate = $totalSalesOrder > 0 ? ($salesOrderCompleted / $totalSalesOrder) * 100 : 0;

        // --- BAGIAN 2: CHART DATA (Group By Status) ---
        
        $getChartData = function ($modelClass) use ($startDate, $endDate) {
            return $modelClass::query()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->when($this->filterSales, fn($q) => $q->where('sales_id', $this->filterSales))
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
        };

        return [
            'salesUsers' => User::pluck('name', 'id'), // Data User untuk Dropdown
            
            // Data Stats
            'totalProspek' => $totalProspek,
            'totalRfq' => $totalRfq,
            'totalQuotation' => $totalQuotation,
            'totalSalesOrder' => $totalSalesOrder,
            'rfqConverted' => $rfqConverted,
            'rfqConversionRate' => $rfqConversionRate,
            'quotationSent' => $quotationSent,
            'quotationSentRate' => $quotationSentRate,
            'quotationAccepted' => $quotationAccepted,
            'quotationAcceptedRate' => $quotationAcceptedRate,
            'salesOrderCompleted' => $salesOrderCompleted,
            'salesOrderCompletedRate' => $salesOrderCompletedRate,

            // Data Charts
            'chartProspek' => $getChartData(Prospek::class),
            'chartRfq' => $getChartData(Rfq::class),
            'chartQuotation' => $getChartData(Quotation::class),
            'chartSalesOrder' => $getChartData(SalesOrder::class),
        ];
    }
}