<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
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
                    ->label('No')
                    ->sortable()
                    ->searchable()
                    ->state(fn(Invoice $record): string => 'INV-0000' . $record->id)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('client.name')
                    ->label('Name')
                    ->description(function (Invoice $record) {
                        $date = Carbon::parse($record->due_date)->format('d M Y');
                        return new HtmlString("<span style='color:oklch(70.4% 0.191 22.216);'>Due: {$date}</span>");
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('items.item_name')
                    ->label('Products')
                    ->listWithLineBreaks()
                    // ->badge()
                    // ->color('info')
                    // ->limit(1)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('company')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('total_amount')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
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
                    )
                    ->toggleable(isToggledHiddenByDefault: false),
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
