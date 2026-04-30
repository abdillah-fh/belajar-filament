<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Client;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceForm
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

        // 4. Hitung nominal pajak dan diskon
        $taxAmount = $subtotal * ($taxPercent / 100);
        $discountAmount = $subtotal * ($discountPercent / 100);

        // 5. Hitung Grand Total
        $grandTotal = $subtotal + $taxAmount - $discountAmount;

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
                Section::make('Detail Invoice')->schema([
                    Grid::make(3)->schema([
                        Select::make('status')
                            ->options([
                                'unpaid' => 'UNPAID',
                                'paid' => 'PAID',
                                'pending' => 'PENDING',
                            ])
                            ->required()
                            ->default('unpaid')
                            ->native(false),
                        DatePicker::make('invoice_date')
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->required(),
                        DatePicker::make('due_date')
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->required(),
                    ])
                ])->collapsible(),


                // Section 2: Detail Client
                Section::make('Detail Client')->schema([
                    Grid::make(2)->schema([
                        // Kolom Kiri
                        Section::make()->schema([
                            Select::make('client_id')
                                ->label('Name')
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
                            TextInput::make('client_phone')->label('Phone')->disabled()->dehydrated(),
                        ])->contained(false),

                        // Kolom Kanan
                        Section::make()->schema([
                            TextInput::make('company')->label('Company')->required(),
                            TextInput::make('client_address')->label('Address')->disabled()->dehydrated(),
                            TextInput::make('client_city')->label('City')->disabled()->dehydrated(),
                            TextInput::make('client_country')->label('Country')->disabled()->dehydrated(),
                        ])->contained(false),
                    ])
                ])->collapsible(),


                //Section 3: Detail Items
                Section::make('Detail Items')->schema([
                    Repeater::make('items')
                        ->label('Item')
                        ->relationship('items')
                        ->live()
                        ->afterStateUpdated(fn($get, $set) => self::updateGrandTotal($get, $set))
                        ->schema([
                            TextInput::make('item_name')
                                ->required()
                                ->columnSpan(['lg' => 12, 'xl' => 5]),

                            TextInput::make('quantity')
                                ->label('Qty')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->required()
                                ->columnSpan(['lg' => 4, 'xl' => 1])
                                ->live(debounce: 500)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $qty = (int) ($state ?? 0);
                                    $price = (int) ($get('unit_price') ?? 0);

                                    // Kalikan dan set ke subtotal_display dengan format ribuan
                                    $set('subtotal_display', number_format($qty * $price, 0, ',', '.'));
                                }),

                            TextInput::make('unit_price')
                                ->numeric()
                                ->prefix('Rp')
                                ->required()
                                ->columnSpan(['lg' => 4, 'xl' => 3])
                                ->live(debounce: 500)
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

                                    // Kalikan dan set ke subtotal_display dengan format ribuan
                                    $set('subtotal_display', number_format($qty * $price, 0, ',', '.'));
                                }),

                            TextInput::make('subtotal_display')
                                ->label('Subtotal')
                                ->prefix('Rp')
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(['lg' => 4, 'xl' => 3])
                                ->afterStateHydrated(function (callable $set, callable $get) {
                                    $qty = (int) ($get('quantity') ?? 0);
                                    $price = (int) ($get('unit_price') ?? 0);

                                    $set('subtotal_display', number_format($qty * $price, 0, ',', '.'));
                                }),

                        ])
                        ->columns(12)
                        ->addActionLabel('Add item'),
                ])->collapsible(),

                Section::make('Summary')->schema([
                    Section::make()->schema([
                        TextInput::make('note')->columnSpan(6),

                        TextInput::make('tax_percentage')
                            ->label('Tax %')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->live(debounce: 500)
                            ->columnSpan(3)
                            ->afterStateUpdated(fn($get, $set) => self::updateGrandTotal($get, $set)),

                        TextInput::make('discount_percentage')
                            ->label('Discount %')
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
