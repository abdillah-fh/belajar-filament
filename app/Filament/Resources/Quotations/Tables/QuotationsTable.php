<?php

namespace App\Filament\Resources\Quotations\Tables;

use App\Models\Quotation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Nomor')
                    ->searchable()
                    ->state(fn(Quotation $record): string => 'QUO-0000' . $record->id)
                    ->color('info'),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->description(fn(Quotation $record): string => $record->company)
                    ->toggleable(),
                TextColumn::make('client.name')
                    ->label('Klien')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('quo_date')
                    ->label('Tanggal')
                    ->date('d F y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('note')
                    ->label('Catatan')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->headerTooltip('Klik status untuk mengubah')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'sent' => 'warning',
                        'rejected' => 'danger',
                        'approved' => 'info',
                        'invoiced' => 'success',
                    })
                    ->searchable()
                    ->formatStateUsing(fn($state) => strtoupper($state))
                    ->action(
                        Action::make('changeStatus')
                            ->schema([
                                Select::make('status')
                                    ->options([
                                        'sent' => 'SENT',
                                        'rejected' => 'REJECTED',
                                        'approved' => 'APPROVED',
                                        'invoiced' => 'INVOICED',
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
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->numeric()
                    ->money('idr', decimalPlaces: 0)
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->tooltip('Lihat'),
                Action::make('cetak_pdf')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success') // Memberi warna hijau
                    ->url(fn(Quotation $record) => route('quotation.pdf', $record))
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
