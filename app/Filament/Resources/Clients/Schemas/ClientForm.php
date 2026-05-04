<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // Select::make('team_id')
                //     ->relationship('team', 'name')
                //     ->required(),
                Grid::make(2)->schema([
                    Section::make()->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email(),
                        TextInput::make('phone')
                            ->label('No HP')
                            ->tel(),
                    ])->contained(false),
                    Section::make()->schema([
                        TextInput::make('address')
                            ->label('Alamat'),
                        TextInput::make('city')
                            ->label('Kota/Kab'),
                        TextInput::make('country')
                            ->label('Negara'),

                    ])->contained(false)
                ])
            ]);
    }
}
