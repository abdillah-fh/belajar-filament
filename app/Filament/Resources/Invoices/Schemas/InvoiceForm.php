<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Client;
use App\Models\Quotation;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

        // --- Tampilkan Subtotal Murni ke field Summary ---
        $set('summary_subtotal_display', number_format($subtotal, 0, ',', '.'));
        $set('subtotal', $subtotal);

        // 3. Ambil nilai pajak dan diskon
        $taxPercent = (float) ($get('tax_percentage') ?? 0);
        $discountPercent = (float) ($get('discount_percentage') ?? 0);

        // 4. Hitung diskon
        $discountAmount = $subtotal * ($discountPercent / 100);

        // --- TAMPILKAN NOMINAL DISKON KE SUMMARY ---
        $set('discount_amount_display', number_format($discountAmount, 0, ',', '.'));
        $set('discount_amount', $discountAmount);

        // 5. Hitung subtotal setelah diskon
        $subtotalAfterDiscount = $subtotal - $discountAmount;

        // 6. Hitung pajak PPN
        $taxAmount = $subtotalAfterDiscount * ($taxPercent / 100);

        // --- TAMPILKAN NOMINAL PPN KE SUMMARY ---
        $set('tax_amount_display', number_format($taxAmount, 0, ',', '.'));
        $set('tax_amount', $taxAmount);

        // 7. Hitung total PPh dari semua item yang checkbox-nya bernilai true
        $totalPph = $items->sum(function ($item) {
            if (!empty($item['is_pph']) && $item['is_pph'] == true) {
                // Hitung ulang harga baris ini lalu kalikan 2% secara langsung
                $qty = (float) str_replace('.', '', $item['quantity'] ?? 0);
                $price = (float) str_replace('.', '', $item['unit_price'] ?? 0);
                return ($qty * $price) * 0.02; // PPh 2%
            }
            return 0;
        });

        // --- SIMPAN STATE TOTAL PPh UNTUK SUMMARY ---
        $set('total_pph_amount_display', number_format($totalPph, 0, ',', '.'));
        $set('total_pph_amount', $totalPph);

        // 8. Hitung grand total setelah pajak
        $grandTotal = ($subtotalAfterDiscount + $taxAmount) - $totalPph;

        // 9. Update tampilan (display) dan field yang akan disimpan ke DB (total_amount)
        $set('grand_total_display', number_format($grandTotal, 0, ',', '.'));
        $set('total_amount', $grandTotal);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([

                // Section 1: Detail Client
                Section::make('Informasi Quotation')->schema([
                    Grid::make(3)->schema([
                        Select::make('is_active')
                            ->label('Ambil dari Quotation?')
                            ->belowContent('Hubungkan dengan Quotation jika project-nya sama')
                            ->options([
                                'no' => 'Tidak',
                                'yes' => 'Ya',
                            ])
                            ->live()
                            ->native(false)
                            ->required()
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (! $state || $state === 'no') {
                                    $set('quotation_id', null);
                                    return;
                                }
                            })
                            ->afterStateHydrated(function ($state, callable $set, $record) {
                                if ($record && $record->quotation_id) {
                                    $set('is_active', 'yes');
                                } else {
                                    $set('is_active', 'no');
                                }
                            }),

                        Select::make('quotation_id')
                            ->label('Pilih Quotation')
                            ->belowContent('Hanya yang berstatus INVOICED yang dapat dipilih')
                            ->relationship(
                                name: 'quotation',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn($query) => $query->where('status', 'invoiced'),
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn($record) =>
                                "{$record->name} - {$record->company}"
                            )
                            ->searchable(['company', 'name'])
                            ->preload()
                            ->required()
                            ->live()
                            ->visible(fn($get) => $get('is_active') === 'yes')
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (! $state) {
                                    $set('client_name', null);
                                    $set('client_email', null);
                                    $set('client_phone', null);
                                    $set('client_address', null);
                                    $set('client_city', null);
                                    $set('client_country', null);
                                    $set('company', null);
                                    return;
                                }
                                $quotation = Quotation::find($state);
                                $set('client_id', $quotation?->client?->id);
                                $set('client_name', $quotation?->client?->name);
                                $set('client_email', $quotation?->client?->email);
                                $set('client_phone', $quotation?->client?->phone);
                                $set('client_address', $quotation?->client?->address);
                                $set('client_city', $quotation?->client?->city);
                                $set('client_country', $quotation?->client?->country);
                                $set('company', $quotation?->company);
                            })
                            ->noOptionsMessage('Belum ada Quotation'),

                        TextInput::make('client_name')
                            ->visible(fn($get) => $get('is_active') === 'yes')
                            ->label('Nama Klien')
                            ->belowContent('Nama otomatis terisi sesuai quotation')
                            ->disabled()
                            ->dehydrated(false)
                    ])
                ])->collapsible(),

                // Section 2: Detail Invoice
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
                            ->label('Tanggal Invoice')
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->required(),
                        DatePicker::make('due_date')
                            ->label('Tanggal Jatuh Tempo')
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
                                // ->visible(fn($get) => $get('is_active') === 'no' || $get('is_active') === null)
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
                                    Grid::make(2)->schema([
                                        Section::make()->schema([
                                            TextInput::make('name')->required(),
                                            TextInput::make('email')->email(),
                                            TextInput::make('phone')->tel(),
                                        ])->contained(false),
                                        Section::make()->schema([
                                            TextInput::make('address'),
                                            TextInput::make('city'),
                                            TextInput::make('country'),
                                        ])->contained(false),
                                    ])
                                ])
                                ->noOptionsMessage('Belum ada klien')
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
                                ->columnSpan(['lg' => 6, 'xl' => 4]),

                            TextInput::make('quantity')
                                ->label('Jumlah')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->required()
                                ->columnSpan(['lg' => 6, 'xl' => 1])
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
                                ->columnSpan(['lg' => 6, 'xl' => 2])
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
                                ->columnSpan(['lg' => 6, 'xl' => 2])
                                ->afterStateHydrated(function (callable $set, callable $get) {
                                    $qty = (int) ($get('quantity') ?? 0);
                                    $price = (int) ($get('unit_price') ?? 0);

                                    $set('subtotal_display', number_format($qty * $price, 0, ',', '.'));
                                }),
                            Hidden::make('subtotal')->default(0),

                            Checkbox::make('is_pph')
                                ->label('PPh')
                                ->inline(false)
                                ->live() // Wajib live agar reaktif
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    // 1. JIKA DICENTANG: Hitung dan isi field di sebelah kanan checkbox
                                    if ($state === true) {
                                        $qty = (float) str_replace('.', '', $get('quantity') ?? 0);
                                        $price = (float) str_replace('.', '', $get('unit_price') ?? 0);

                                        $pphAmount = ($qty * $price) * 0.02;

                                        // Injeksi nilai ke field pph_amount_display (sebelah kanan checkbox)
                                        $set('pph_amount', $pphAmount);
                                        $set('pph_amount_display', number_format($pphAmount, 0, ',', '.'));
                                    }
                                    // 2. JIKA TIDAK DICENTANG: Kosongkan nilainya
                                    else {
                                        $set('pph_amount', 0);
                                        $set('pph_amount_display', '0');
                                    }

                                    // 3. Trigger update ke Grand Total (Summary bawah)
                                    $rootGet = fn($path) => $get('../../' . $path);
                                    $rootSet = fn($path, $value) => $set('../../' . $path, $value);
                                    self::updateGrandTotal($rootGet, $rootSet);
                                }),

                            TextInput::make('pph_amount_display')
                                ->label('Nilai PPh (2%)')
                                ->prefix('Rp')
                                ->disabled()
                                ->dehydrated(false)
                                // 1. pengecekan visible dengan (bool) agar aman jika DB mengembalikan angka 1
                                ->visible(fn(callable $get) => (bool) $get('is_pph') === true)
                                // 2. Tambahkan Hydrated untuk mengisi format angkanya saat halaman Edit dibuka
                                ->afterStateHydrated(function (callable $set, callable $get) {
                                    $pphAmount = (float) ($get('pph_amount') ?? 0);
                                    if ($pphAmount > 0) {
                                        $set('pph_amount_display', number_format($pphAmount, 0, ',', '.'));
                                    }
                                })
                                ->columnSpan(['lg' => 6, 'xl' => 2]),

                            Hidden::make('pph_amount')->default(0),

                        ])
                        ->columns(12)
                        ->addActionLabel('Add item'),
                    Grid::make(4)->schema([
                        Section::make()->schema([
                            TextInput::make('discount_percentage')
                                ->label('Diskon %')
                                ->placeholder('0')
                                ->required()
                                ->numeric()
                                ->suffix('%')
                                ->default(0)
                                ->live(debounce: 500)
                                ->afterStateUpdated(fn($get, $set) => self::updateGrandTotal($get, $set)),

                            TextInput::make('tax_percentage')
                                ->label('PPN %')
                                ->placeholder('0')
                                ->required()
                                ->numeric()
                                ->suffix('%')
                                ->default(0)
                                ->live(debounce: 500)
                                ->afterStateUpdated(fn($get, $set) => self::updateGrandTotal($get, $set)),

                        ])->inlinelabel()->contained(false),
                        Section::make()->schema([])->inlinelabel()->contained(false),
                        Section::make()->schema([
                            Textarea::make('note')->label('Catatan'),
                        ])->contained(false)->columnSpan(2),
                    ]),

                ])->collapsible(),

                //Section 4: Detail Items
                Section::make('Summary')->schema([
                    Section::make()->schema([
                        Grid::make(2)->schema([
                            Section::make()->schema([
                                TextInput::make('summary_subtotal_display')
                                    ->label('Subtotal (Total Item)')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(false),
                                Hidden::make('subtotal')->dehydrated(),

                                TextInput::make('discount_amount_display')
                                    ->label('Diskon')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(false),
                                Hidden::make('discount_amount')->dehydrated(),

                                TextInput::make('tax_amount_display')
                                    ->label('PPN (11%)')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(false),
                                Hidden::make('tax_amount')->dehydrated(),

                                TextInput::make('total_pph_amount_display')
                                    ->label('PPh23 (2%)')
                                    ->prefix('- Rp') // Menggunakan minus agar terlihat memotong tagihan
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visible(fn(callable $get) => (float) $get('total_pph_amount') > 0), // Hanya tampil jika > 0

                                Hidden::make('total_pph_amount')->dehydrated(),

                                TextInput::make('grand_total_display')
                                    ->label('Grand Total')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function (callable $set, callable $get) {
                                        self::updateGrandTotal($get, $set);
                                    }),
                                Hidden::make('total_amount')->dehydrated(),
                            ])->secondary()->inlineLabel()
                        ]),
                    ])->contained(false),



                ])->collapsible(),
            ]);
    }
}
