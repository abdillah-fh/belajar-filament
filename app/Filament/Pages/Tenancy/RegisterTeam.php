<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Team;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class RegisterTeam extends RegisterTenant
{

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user->is_superadmin == true;
    }

    public static function getLabel(): string
    {
        return 'Register team';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
            ]);
    }

    protected function handleRegistration(array $data): Team
    {
        $team = Team::create($data);

        $team->members()->attach(Auth::user());

        return $team;
    }
}
