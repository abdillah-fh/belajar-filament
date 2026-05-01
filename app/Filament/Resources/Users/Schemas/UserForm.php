<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make()->schema([
                    Toggle::make('is_superadmin')
                        ->label('Super Admin')
                        ->inline(false),
                    Select::make('teams')
                        ->relationship('teams', 'name')
                        ->searchable()
                        ->multiple()
                        ->preload()
                        ->native(false)
                        ->createOptionForm([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(100)
                                ->unique()
                                ->live(debounce: 500)
                                ->afterStateUpdated(function ($state, $set) {
                                    $set('slug', str($state)->slug());
                                }),
                            TextInput::make('slug')
                                ->required()
                                ->maxLength(100)
                                ->unique(),
                        ]),
                    TextInput::make('name')
                        ->required(),
                    TextInput::make('email')
                        ->label('Email address')
                        ->email()
                        ->required(),
                    TextInput::make('password')
                        ->password()
                        ->required(fn(string $operation): bool => $operation === 'create')
                        ->same('passwordConfirmation') // Validasi harus sama dengan konfirmasi
                        // Hanya simpan (dehydrate) jika user mengetik sesuatu
                        ->dehydrated(fn($state) => filled($state))
                        // Hash password sebelum masuk ke database
                        ->dehydrateStateUsing(fn($state) => Hash::make($state))
                        ->revealable()
                        ->aboveContent('Leave empty if you don\'t want to change your password.'),
                    TextInput::make('passwordConfirmation')
                        ->label('Confirm Password')
                        ->password()
                        ->required(fn(string $operation): bool => $operation === 'create')
                        ->dehydrated(false) // Tidak perlu disimpan ke DB
                        ->revealable()
                        ->aboveContent('Leave empty if you don\'t want to change your password.'),

                ]),
            ]);
    }
}
