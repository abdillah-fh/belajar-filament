<?php

namespace App\Filament\Pages\Tenancy;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditTeamProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Team profile';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make()->schema([
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
                ])->contained(false),
            ]);
    }

    protected function getRedirectUrl(): ?string
    {
        return filament()->getUrl();
    }
}
