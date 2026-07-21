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
use Filament\Support\Enums\FontWeight;
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
                    Grid::make(['xl' => 3, 'lg' => 2])->schema([
                        Section::make()->schema([
                            TextEntry::make('name')
                                ->label('Nama Proyek:')
                                ->size(TextSize::Large),

                            TextEntry::make('id')
                                ->label('Nomor')
                                ->state(fn(Quotation $record): string => 'QUO-0000' . $record->id)
                                ->color('info')
                                ->badge()
                                ->inlinelabel(),

                            TextEntry::make('client.name')
                                ->label('Klien')
                                ->inlinelabel(),

                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn($state) => strtoupper($state))
                                ->color(fn(string $state): string => match ($state) {
                                    'sent' => 'warning',
                                    'rejected' => 'danger',
                                    'approved' => 'info',
                                    'invoiced' => 'success',
                                })
                                ->inlinelabel()
                                ->size(TextSize::Large),

                            TextEntry::make('note')
                                ->label('Catatan')
                                ->inlinelabel()
                                ->placeholder('-'),

                        ]),
                        Section::make()->schema([
                            TextEntry::make('Rincian Biaya'),
                            TextEntry::make('subtotal')
                                ->label('Harga proyek')
                                ->inlinelabel()
                                ->weight(FontWeight::Bold)
                                ->money('idr', decimalPlaces: 0),

                            TextEntry::make('tax_amount')
                                ->label('PPN (11%)')
                                ->inlinelabel()
                                ->weight(FontWeight::Bold)
                                ->money('idr', decimalPlaces: 0),
                            TextEntry::make('total_pph_amount')
                                ->label('PPh 23 (2%)')
                                ->inlinelabel()
                                ->weight(FontWeight::Bold)
                                ->money('idr', decimalPlaces: 0),
                            TextEntry::make('total_amount')
                                ->label('Total')
                                ->inlinelabel()
                                ->weight(FontWeight::Bold)
                                ->money('idr', decimalPlaces: 0),
                        ]),

                    ]),
                    RepeatableEntry::make('items')
                        ->label('Detail item:')
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
