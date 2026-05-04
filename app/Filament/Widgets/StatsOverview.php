<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class StatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $startDate = $this->pageFilters['startDate'] ?? null;
        $endDate = $this->pageFilters['endDate'] ?? null;

        return [
            Stat::make(
                'Total Invoices',
                Invoice::query()
                    ->when($startDate, fn($query, $startDate) => $query->where('invoice_date', '>=', $startDate))
                    ->when($endDate, fn($query, $endDate) => $query->where('invoice_date', '<=', $endDate))
                    ->count()
            )
                ->icon('heroicon-o-clipboard-document-check')
                ->description('Semua invoices')
                ->color('info'),

            Stat::make(
                'Paid Revenue',
                'Rp ' . number_format(
                    Invoice::query()
                        ->when(
                            $startDate,
                            fn($query) =>
                            $query->whereDate('invoice_date', '>=', $startDate)
                        )
                        ->when(
                            $endDate,
                            fn($query) =>
                            $query->whereDate('invoice_date', '<=', $endDate)
                        )
                        ->where('status', 'paid') // 🔥 kondisi status
                        ->sum('total_amount'),
                    0,
                    ',',
                    '.'
                )
            )
                ->icon('heroicon-o-check-circle')
                ->description('Invoice lunas')
                ->color('success'),

            Stat::make(
                'Unpaid Revenue',
                'Rp ' . number_format(
                    Invoice::query()
                        ->when(
                            $startDate,
                            fn($query) =>
                            $query->whereDate('invoice_date', '>=', $startDate)
                        )
                        ->when(
                            $endDate,
                            fn($query) =>
                            $query->whereDate('invoice_date', '<=', $endDate)
                        )
                        ->where('status', 'unpaid') // 🔥 kondisi status
                        ->sum('total_amount'),
                    0,
                    ',',
                    '.'
                )
            )
                ->icon('heroicon-o-exclamation-circle')
                ->description('Invoice belum lunas')
                ->color('danger'),
        ];
    }
}
