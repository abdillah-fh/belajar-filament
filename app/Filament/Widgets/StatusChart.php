<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class StatusChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;
    protected ?string $heading = 'Status Invoice';

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'data' => [
                        Invoice::where('status', 'paid')->count(),
                        Invoice::where('status', 'unpaid')->count(),
                        Invoice::where('status', 'pending')->count(),
                    ],
                    'backgroundColor' => [
                        'rgb(47, 107, 63)',
                        'rgb(251, 65, 65)',
                        'rgb(255, 205, 86)'
                    ],
                ],
            ],
            'labels' => [
                'Paid',
                'Unpaid',
                'Pending',
            ]
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
