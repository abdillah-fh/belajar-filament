<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
// 🔥 Menggunakan Schema untuk v5
use Filament\Schemas\Schema;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.settings';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(
            auth()->user()->only('name', 'email')
        );
    }

    // 🔥 Parameter dan Return dirubah ke Schema
    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Alamat Email')
                    ->email()
                    ->required()
                    ->maxLength(255),

                TextInput::make('password')
                    ->label('Password Baru')
                    ->password()
                    ->dehydrateStateUsing(fn($state) => Hash::make($state))
                    ->dehydrated(fn($state) => filled($state))
                    ->minLength(8)
                    ->same('passwordConfirmation'),

                TextInput::make('passwordConfirmation')
                    ->label('Konfirmasi Password Baru')
                    ->password()
                    ->dehydrated(false),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        auth()->user()->update($data);

        Notification::make()
            ->success()
            ->title('Profil berhasil diperbarui!')
            ->send();
    }
}
