<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use App\Models\Quotation;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class ViewQuotation extends ViewRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Quotation')->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('name')
                            ->label('Nama Quotation:')
                            ->belowContent(
                                Text::make('Nomor')
                                    ->content(fn(Quotation $record): string => 'QUO-0000' . $record->id)
                                    ->color('info')
                                    ->size(TextSize::Large)
                                    ->badge(),
                            )
                            ->size(TextSize::Large),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn($state) => strtoupper($state))
                            ->color(fn(string $state): string => match ($state) {
                                'sent' => 'warning',
                                'rejected' => 'danger',
                                'approved' => 'info',
                                'invoiced' => 'success',
                            })
                            ->size(TextSize::Large),

                        TextEntry::make('client.name')
                            ->label('Klien:')
                            ->size(TextSize::Large),
                        TextEntry::make('total_amount')
                            ->label('Total:')
                            ->size(TextSize::Large)
                            ->money('idr', decimalPlaces: 0),
                        TextEntry::make('note')
                            ->label('Catatan:')
                            ->size(TextSize::Large),
                    ]),
                    RepeatableEntry::make('items')
                        ->label('Detail Produk:')
                        ->table([
                            TableColumn::make('Produk'),
                            TableColumn::make('Jml'),
                            TableColumn::make('Harga'),
                            TableColumn::make('Subtotal'),
                        ])
                        ->schema([
                            TextEntry::make('item_name'),
                            TextEntry::make('quantity'),
                            TextEntry::make('unit_price')
                                ->money('idr', decimalPlaces: 0)
                                ->alignEnd(),
                            TextEntry::make('subtotal')
                                ->money('idr', decimalPlaces: 0)
                                ->alignEnd(),
                        ]),
                ])->icon(Heroicon::InformationCircle)->collapsible(),
            ])->columns(1);
    }
}
