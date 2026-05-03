<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use App\Models\Quotation;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListQuotations extends ListRecords
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make()
                ->badge(Quotation::query()->count())
                ->badgeColor('primary'),
            'sent' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'sent'))
                ->badge(Quotation::query()->where('status', 'sent', true)->count())
                ->badgeColor('warning'),
            'rejected' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'rejected'))
                ->badge(Quotation::query()->where('status', 'rejected', true)->count())
                ->badgeColor('danger'),
            'approved' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'approved'))
                ->badge(Quotation::query()->where('status', 'approved', true)->count())
                ->badgeColor('info'),
            'invoiced' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'invoiced'))
                ->badge(Quotation::query()->where('status', 'invoiced', true)->count())
                ->badgeColor('success'),
        ];
    }
}
