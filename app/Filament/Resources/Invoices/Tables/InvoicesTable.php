<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                // TextColumn::make('team.name')
                //     ->searchable(),
                TextColumn::make('id')
                    ->label('No')
                    ->state(fn(Invoice $record): string => 'INV-' . $record->id),
                TextColumn::make('client.name')
                    ->label('Name')
                    ->description(function (Invoice $record) {
                        $date = Carbon::parse($record->due_date)->format('d M Y');
                        return new HtmlString("<span style='color:oklch(70.4% 0.191 22.216);'>Due: {$date}</span>");
                    })
                    ->searchable(),
                TextColumn::make('total_amount')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
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
                            }),
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
                TextColumn::make('company')
                    ->searchable(),
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
                EditAction::make(),
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
