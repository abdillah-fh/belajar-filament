<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    Select::make('range')
                        ->label('Filter berdasarkan waktu')
                        ->options([
                            'all' => 'Semua Waktu',
                            'today' => 'Hari Ini',
                            'week' => 'Minggu Ini',
                            'month' => 'Bulan Ini',
                            'year' => 'Tahun Ini',
                            'custom' => 'Pilih tanggal',
                        ])
                        ->default('year')
                        ->live()
                        ->native(false)
                        ->afterStateUpdated(function ($state, callable $set) {
                            match ($state) {
                                'today' => [
                                    $set('startDate', Carbon::today()),
                                    $set('endDate', Carbon::today()),
                                ],
                                'week' => [
                                    $set('startDate', Carbon::now()->startOfWeek()),
                                    $set('endDate', Carbon::now()->endOfWeek()),
                                ],
                                'month' => [
                                    $set('startDate', Carbon::now()->startOfMonth()),
                                    $set('endDate', Carbon::now()->endOfMonth()),
                                ],
                                'year' => [
                                    $set('startDate', Carbon::now()->startOfYear()),
                                    $set('endDate', Carbon::now()->endOfYear()),
                                ],
                                'custom' => [
                                    // jangan set apa-apa → biar user isi manual
                                ],
                                default => [
                                    $set('startDate', null),
                                    $set('endDate', null),
                                ],
                            };
                        }),
                    DatePicker::make('startDate')
                        ->label('Start Date')
                        ->visible(fn($get) => $get('range') === 'custom')
                        ->live(),

                    DatePicker::make('endDate')
                        ->label('End Date')
                        ->visible(fn($get) => $get('range') === 'custom')
                        ->live(),
                ])->columns(3)->columnSpanFull(),
            ]);
    }
}
