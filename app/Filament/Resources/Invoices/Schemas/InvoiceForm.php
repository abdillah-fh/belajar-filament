<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Client;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
// use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
// use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class InvoiceForm
{
    protected static function calculateGrandTotal($get, $set)
    {
        $items = collect($get('items') ?? []);

        $subtotal = $items->sum(fn($item) => $item['subtotal'] ?? 0);

        $tax = (float) ($get('tax_percentage') ?? 0);
        $discount = (float) ($get('discount_percentage') ?? 0);

        $taxAmount = $subtotal * ($tax / 100);
        $discountAmount = $subtotal * ($discount / 100);

        $grandTotal = $subtotal + $taxAmount - $discountAmount;

        $set('grand_total_display', 'IDR ' . number_format($grandTotal, 0, ',', '.'));
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            // ->components([
            //     Select::make('team_id')
            //         ->relationship('team', 'name')
            //         ->required(),
            //     Select::make('client_id')
            //         ->relationship('client', 'name')
            //         ->required(),
            //     TextInput::make('status')
            //         ->required()
            //         ->default('unpaid'),
            //     DatePicker::make('invoice_date')
            //         ->required(),
            //     DatePicker::make('due_date')
            //         ->required(),
            //     TextInput::make('client_name'),
            //     TextInput::make('client_email')
            //         ->email(),
            //     TextInput::make('client_phone')
            //         ->tel(),
            //     TextInput::make('client_address'),
            //     TextInput::make('client_city'),
            //     TextInput::make('client_country'),
            //     TextInput::make('company'),
            //     Textarea::make('note')
            //         ->columnSpanFull(),
            //     TextInput::make('tax_percentage')
            //         ->required()
            //         ->numeric()
            //         ->default(0.0),
            //     TextInput::make('discount_percentage')
            //         ->required()
            //         ->numeric()
            //         ->default(0.0),
            //     TextInput::make('total_amount')
            //         ->required()
            //         ->numeric()
            //         ->default(0.0),
            // ]);
            // ->columns(1)
            ->components([
                Section::make()
                    ->schema([
                        Section::make('Detail Invoice')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('status')
                                            ->options([
                                                'unpaid' => 'Unpaid',
                                                'pending' => 'Pending',
                                                'paid' => 'Paid',
                                            ])
                                            ->default('unpaid')
                                            ->required()
                                            ->native(false),
                                        DatePicker::make('invoice_date')
                                            ->default(now())
                                            ->required(),
                                        DatePicker::make('due_date')
                                            ->required(),
                                    ])
                            ])
                            ->collapsible(),

                        Section::make('Detail Client')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        // Kiri
                                        Section::make()
                                            ->schema([
                                                Select::make('client_id')
                                                    ->label('Name')
                                                    ->relationship('client', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->live()
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        $client = Client::find($state);

                                                        if ($client) {
                                                            $set('client_email', $client->email);
                                                            $set('client_phone', $client->phone);
                                                            $set('client_address', $client->address);
                                                            $set('client_city', $client->city);
                                                            $set('client_country', $client->country);
                                                        }
                                                    })
                                                    ->createOptionForm([
                                                        TextInput::make('name')
                                                            ->required(),
                                                        TextInput::make('email')
                                                            ->label('Email address')
                                                            ->email(),
                                                        TextInput::make('phone')
                                                            ->tel(),
                                                        TextInput::make('address'),
                                                        TextInput::make('city'),
                                                        TextInput::make('country'),
                                                    ])
                                                    ->required(),
                                                TextInput::make('client_email')->label('Email')->disabled()->dehydrated(),
                                                TextInput::make('client_phone')->label('Phone')->disabled()->dehydrated(),
                                            ])
                                            ->contained(false),
                                        // Kanan
                                        Section::make()
                                            ->schema([
                                                TextInput::make('company')->label('Company')->required(),
                                                TextInput::make('client_address')->label('Address')->disabled()->dehydrated(),
                                                TextInput::make('client_city')->label('City')->disabled()->dehydrated(),
                                                TextInput::make('client_country')->label('Country')->disabled()->dehydrated(),
                                            ])
                                            ->contained(false),
                                    ]),
                            ])
                            ->collapsible(),

                        Section::make('Detail Items')
                            ->schema([
                                Repeater::make('items')
                                    ->label('Item')
                                    ->relationship('items')
                                    ->live()
                                    ->schema([
                                        TextInput::make('item_name')
                                            ->required()
                                            ->columnSpan(['lg' => 12, 'xl' => 5]),

                                        TextInput::make('quantity')
                                            ->label('Qty')
                                            ->numeric()
                                            ->default(1)
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                $qty = (int) str_replace('.', '', $state ?? 0);
                                                $price = (int) str_replace('.', '', $get('unit_price') ?? 0);

                                                $subtotal = $qty * $price;

                                                $set('subtotal', $subtotal);
                                                $set('subtotal_display', 'IDR ' . number_format($subtotal, 0, ',', '.'));
                                            })
                                            ->required()
                                            ->columnSpan(['lg' => 4, 'xl' => 1]),

                                        TextInput::make('unit_price')
                                            ->numeric()
                                            ->prefix('IDR')
                                            ->live()
                                            ->mask(RawJs::make(<<<'JS'
                                                $input => {
                                                    return $input
                                                        .replace(/\D/g, '')
                                                        .replace(/\B(?=(\d{3})+(?!\d))/g, '.')
                                                }
                                            JS))
                                            ->stripCharacters('.')
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                $qty = (int) str_replace('.', '', $get('quantity') ?? 0);
                                                $price = (int) str_replace('.', '', $state ?? 0);

                                                $subtotal = $qty * $price;

                                                $set('subtotal', $subtotal);
                                                $set('subtotal_display', 'IDR ' . number_format($subtotal, 0, ',', '.'));
                                            })
                                            ->required()
                                            ->columnSpan(['lg' => 4, 'xl' => 3]),

                                        // 🔥 DISPLAY SAJA (tidak ada logic di sini)
                                        TextInput::make('subtotal_display')
                                            ->label('Subtotal')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columnSpan(['lg' => 4, 'xl' => 3]),

                                        Hidden::make('subtotal')
                                            ->dehydrated(),
                                    ])
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        self::calculateGrandTotal($get, $set);
                                    })
                                    ->columns(12)
                                    ->addActionLabel('Add item'),
                            ])
                            ->collapsible(),

                        Section::make('Summary')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextInput::make('note')->columnSpan(6),
                                        TextInput::make('tax_percentage')
                                            ->label('Tax')
                                            ->numeric()
                                            ->suffix('%')
                                            ->afterStateUpdated(fn($state, $set, $get) => self::calculateGrandTotal($get, $set))
                                            ->default(0)
                                            ->columnSpan(3),
                                        TextInput::make('discount_percentage')
                                            ->label('Discount')
                                            ->numeric()
                                            ->suffix('%')
                                            ->live()
                                            ->afterStateUpdated(fn($state, $set, $get) => self::calculateGrandTotal($get, $set))
                                            ->default(0)
                                            ->columnSpan(3),
                                    ])->contained(false)->columns(12),

                                TextInput::make('grand_total_display')
                                    ->label('Grand Total')
                                    ->disabled()
                                    ->dehydrated(false),
                            ])
                            ->collapsible(),
                    ])
                    ->columnSpanFull()
            ]);
    }
}
