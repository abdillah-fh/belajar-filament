<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use App\Models\Quotation;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->striped()
            ->columns([
                // TextColumn::make('team.name')
                //     ->searchable(),
                TextColumn::make('id')
                    ->label('Nomor')
                    ->color('primary')
                    ->sortable()
                    ->searchable()
                    ->state(fn(Invoice $record): string => 'INV-0000' . $record->id),
                TextColumn::make('quotation.name')
                    ->label('Sumber Quotation (Nama proyek)')
                    ->placeholder('Tanpa Quotation')
                    ->description(function (Invoice $record) {
                        $no = $record->quotation?->id;
                        if (! $no) {
                            return '-';
                        }
                        return new \Illuminate\Support\HtmlString(
                            "<span style='color:oklch(68.5% 0.169 237.323);'>QUO-0000{$no}</span>"
                        );
                    })
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('client.name')
                    ->label('Klien')
                    ->description(function (Invoice $record) {
                        return $record->company;
                    })
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('invoice_date')
                    ->label('Tanggal')
                    ->date('d M y')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('items.item_name')
                    ->label('Item')
                    ->listWithLineBreaks()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('note')
                    ->label('Catatan')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->headerTooltip('Klik status untuk mengubah')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'danger',
                        'pending' => 'warning',
                    })
                    ->searchable()
                    ->formatStateUsing(fn($state) => strtoupper($state))
                    ->action(
                        Action::make('changeStatus')
                            ->schema([
                                Select::make('status')
                                    ->options([
                                        'unpaid' => 'UNPAID',
                                        'paid' => 'PAID',
                                        'pending' => 'PENDING',
                                    ])
                                    ->native(false)
                                    ->selectablePlaceholder(false),
                            ])
                            ->fillForm(fn($record) => [
                                'status' => $record->status,
                            ])
                            ->action(function ($record, $data) {
                                $record->update([
                                    'status' => $data['status'],
                                ]);

                                Notification::make()
                                    ->title('Status berhasil diubah')
                                    ->success()
                                    ->send();
                            })
                    ),
                // SelectColumn::make('status')
                //     ->options([
                //         'unpaid' => 'UNPAID',
                //         'paid' => 'PAID',
                //         'pending' => 'PENDING',
                //     ])
                //     ->native(false)
                //     ->selectablePlaceholder(false)
                //     ->afterStateUpdated(function ($record, $state) {
                //         Notification::make()
                //             ->title('Status berhasil diubah')
                //             ->body("Status sekarang: {$state}")
                //             ->success()
                //             ->send();
                //     }),
                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->summarize(Sum::make()->money('idr', decimalPlaces: 0))
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('tax_amount')
                    ->label('PPN')
                    ->summarize(Sum::make()->money('idr', decimalPlaces: 0))
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->summarize(Sum::make()->money('idr', decimalPlaces: 0))
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('invoice_date')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('Dari'),
                        DatePicker::make('created_until')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('invoice_date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('invoice_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators[] = Indicator::make('Dari ' . Carbon::parse($data['created_from'])->toFormattedDateString())
                                ->removeField('created_from');
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators[] = Indicator::make('Sampai ' . Carbon::parse($data['created_until'])->toFormattedDateString())
                                ->removeField('created_until');
                        }

                        return $indicators;
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('cetak_pdf')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success') // Memberi warna hijau
                    ->url(fn(Invoice $record) => route('invoices.pdf', $record))
                    ->openUrlInNewTab() // Buka di tab baru agar aplikasi tidak tertutup
                    ->iconButton()
                    ->tooltip('Download'),
                EditAction::make()
                    ->color('warning')
                    ->iconButton()
                    ->tooltip('Edit'),
                DeleteAction::make()
                    ->color('danger')
                    ->iconButton()
                    ->tooltip('Delete'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
