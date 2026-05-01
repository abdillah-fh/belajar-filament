<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected function getStats(): array
    {
        return [
            Stat::make('Total Invoices', Invoice::count())
                ->icon('heroicon-o-clipboard-document-check')
                ->description('Semua invoices')
                ->color('info'),
            Stat::make('Paid Revenue', 'Rp ' . number_format(Invoice::where('status', 'paid')->sum('total_amount'), 0, ',', '.'))
                ->icon('heroicon-o-check-circle')
                ->description('Invoice lunas')
                ->color('success'),
            Stat::make('Unpaid Revenue', 'Rp ' . number_format(Invoice::where('status', 'unpaid')->sum('total_amount'), 0, ',', '.'))
                ->icon('heroicon-o-exclamation-circle')
                ->description('Invoice belum lunas')
                ->color('danger'),
        ];
    }
}
