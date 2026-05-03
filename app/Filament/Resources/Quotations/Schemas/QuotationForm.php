<?php

namespace App\Filament\Resources\Quotations\Schemas;

use App\Models\Client;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
// use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuotationForm
{
    protected static function updateGrandTotal(callable $get, callable $set): void
    {
        // 1. Ambil semua item dari repeater
        $items = collect($get('items') ?? []);

        // 2. Hitung subtotal (penjumlahan qty * unit_price tiap baris)
        $subtotal = $items->sum(function ($item) {
            // Bersihkan format titik jika ada (dari masking)
            $qty = (float) str_replace('.', '', $item['quantity'] ?? 0);
            $price = (float) str_replace('.', '', $item['unit_price'] ?? 0);
            return $qty * $price;
        });

        // 3. Ambil nilai pajak dan diskon
        $taxPercent = (float) ($get('tax_percentage') ?? 0);
        $discountPercent = (float) ($get('discount_percentage') ?? 0);

        // 4. Hitung diskon
        $discountAmount = $subtotal * ($discountPercent / 100);

        // 5. Hitung grand total setelah diskon
        $grandTotal = $subtotal - $discountAmount;

        // 6. Hitung pajak
        $taxAmount = $grandTotal * ($taxPercent / 100);

        // 6. Hitung grand total setelah pajak
        $grandTotal = $grandTotal + $taxAmount;

        // 6. Update tampilan (display) dan field yang akan disimpan ke DB (total_amount)
        $set('grand_total_display', number_format($grandTotal, 0, ',', '.'));
        $set('total_amount', $grandTotal);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // Section 1 : Detail Invoice
                Section::make('Detail Quotation')->schema([
                    TextInput::make('name')
                        ->label('Nama')
                        ->required()
                        ->placeholder('Contoh: Pembuatan Website Company Profile')
                        ->maxLength(255),
                    Grid::make(2)->schema([
                        Select::make('status')
                            ->options([
                                'sent' => 'SENT',
                                'approved' => 'APPROVED',
                                'rejected' => 'REJECTED',
                                'invoiced' => 'INVOICED',
                            ])
                            ->required()
                            ->default('sent')
                            ->native(false),
                        DatePicker::make('quo_date')
                            ->label('Tanggal')
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->required(),
                    ])
                ])->collapsible(),


                // Section 2: Detail Client
                Section::make('Detail Klien')->schema([
                    Grid::make(2)->schema([
                        // Kolom Kiri
                        Section::make()->schema([
                            Select::make('client_id')
                                ->label('Nama Klien')
                                ->relationship('client', 'name')
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, $set) {
                                    $client = Client::find($state);
                                    if ($client) {
                                        $set('client_name', $client->name);
                                        $set('client_email', $client->email);
                                        $set('client_phone', $client->phone);
                                        $set('client_address', $client->address);
                                        $set('client_city', $client->city);
                                        $set('client_country', $client->country);
                                    }
                                })
                                ->createOptionForm([
                                    TextInput::make('name')->required(),
                                    TextInput::make('email')->email(),
                                    TextInput::make('phone')->tel(),
                                    TextInput::make('address'),
                                    TextInput::make('city'),
                                    TextInput::make('country'),
                                ])
                                ->required(),
                            Hidden::make('client_name')->dehydrated(),
                            TextInput::make('client_email')->label('Email')->disabled()->dehydrated(),
                            TextInput::make('client_phone')->label('No HP')->disabled()->dehydrated(),
                        ])->contained(false),

                        // Kolom Kanan
                        Section::make()->schema([
                            TextInput::make('company')->label('Perusahaan')->required(),
                            TextInput::make('client_address')->label('Alamat')->disabled()->dehydrated(),
                            TextInput::make('client_city')->label('Kota/Kab')->disabled()->dehydrated(),
                            TextInput::make('client_country')->label('Negara')->disabled()->dehydrated(),
                        ])->contained(false),
                    ])
                ])->collapsible(),


                //Section 3: Detail Items
                Section::make('Detail Produk/Layanan')->schema([
                    Repeater::make('items')
                        ->label('Detail')
                        ->relationship('items')
                        ->live()
                        ->afterStateUpdated(fn($get, $set) => self::updateGrandTotal($get, $set))
                        ->schema([
                            TextInput::make('item_name')
                                ->label('Produk/Layanan')
                                ->required()
                                ->columnSpan(['lg' => 12, 'xl' => 5]),

                            TextInput::make('quantity')
                                ->label('Jumlah')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->required()
                                ->columnSpan(['lg' => 4, 'xl' => 1])
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $qty = (int) ($state ?? 0);
                                    $price = (int) ($get('unit_price') ?? 0);
                                    $subtotal = $qty * $price;

                                    // Update nilai murni ke DB
                                    $set('subtotal', $subtotal);

                                    // Kalikan dan set ke subtotal_display dengan format ribuan
                                    $set('subtotal_display', number_format($qty * $price, 0, ',', '.'));
                                }),

                            TextInput::make('unit_price')
                                ->label('Harga Satuan')
                                ->numeric()
                                ->prefix('Rp')
                                ->required()
                                ->columnSpan(['lg' => 4, 'xl' => 3])
                                ->live(onBlur: true)
                                // ->mask(RawJs::make(<<<'JS'
                                //     $input => {
                                //         return $input
                                //             .replace(/\D/g, '')
                                //             .replace(/\B(?=(\d{3})+(?!\d))/g, '.')
                                //     }
                                // JS))
                                // ->stripCharacters('.')
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $qty = (int) ($get('quantity') ?? 0);
                                    $price = (int) ($state ?? 0);
                                    $subtotal = $qty * $price;

                                    $set('subtotal', $subtotal);

                                    // Kalikan dan set ke subtotal_display dengan format ribuan
                                    $set('subtotal_display', number_format($qty * $price, 0, ',', '.'));
                                }),

                            TextInput::make('subtotal_display')
                                ->label('Total')
                                ->prefix('Rp')
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(['lg' => 4, 'xl' => 3])
                                ->afterStateHydrated(function (callable $set, callable $get) {
                                    $qty = (int) ($get('quantity') ?? 0);
                                    $price = (int) ($get('unit_price') ?? 0);

                                    $set('subtotal_display', number_format($qty * $price, 0, ',', '.'));
                                }),
                            Hidden::make('subtotal')->default(0),

                        ])
                        ->columns(12)
                        ->addActionLabel('Add item'),
                ])->collapsible(),

                Section::make('Summary')->schema([
                    Section::make()->schema([
                        TextInput::make('note')->label('Catatan')->columnSpan(6),

                        TextInput::make('discount_percentage')
                            ->label('Diskon %')
                            ->required()
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->live(debounce: 500)
                            ->columnSpan(3)
                            ->afterStateUpdated(fn($get, $set) => self::updateGrandTotal($get, $set)),

                        TextInput::make('tax_percentage')
                            ->label('Pajak %')
                            ->required()
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->live(debounce: 500)
                            ->columnSpan(3)
                            ->afterStateUpdated(fn($get, $set) => self::updateGrandTotal($get, $set)),
                    ])->contained(false)->columns(12),

                    Section::make()->schema([
                        TextInput::make('grand_total_display')
                            ->label('Grand Total')
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (callable $set, callable $get) {
                                self::updateGrandTotal($get, $set);
                            }),

                        Hidden::make('total_amount')->dehydrated(),
                    ])->contained(false),

                ])->collapsible(),
            ]);
    }
}
