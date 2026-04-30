<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->badge(Invoice::query()->count())
                ->badgeColor('primary'),
            'paid' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'paid'))
                ->badge(Invoice::query()->where('status', 'paid', true)->count())
                ->badgeColor('success'),
            'unpaid' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'unpaid'))
                ->badge(Invoice::query()->where('status', 'unpaid', true)->count())
                ->badgeColor('danger'),
            'pending' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'pending'))
                ->badge(Invoice::query()->where('status', 'pending', true)->count())
                ->badgeColor('warning'),
        ];
    }
}
