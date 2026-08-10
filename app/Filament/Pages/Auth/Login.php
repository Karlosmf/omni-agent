<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function getHeading(): string
    {
        return 'Portal del Cliente';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                $this->getIdentifierFormComponent(),
                $this->getDocumentFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    protected function getIdentifierFormComponent(): Component
    {
        return TextInput::make('identifier')
            ->label('Email o Teléfono')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    protected function getDocumentFormComponent(): Component
    {
        return TextInput::make('document')
            ->label('DNI o Pasaporte')
            ->password()
            ->required();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'identifier' => $data['identifier'],
            'document' => $data['document'],
        ];
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            throw ValidationException::withMessages([
                'data.identifier' => __('filament-panels::pages/auth/login.messages.throttled', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]),
            ]);
        }

        $data = $this->form->getState();

        $identifier = trim($data['identifier']);
        $document = trim($data['document']);

        $user = User::where('role', 'customer')
            ->where(function ($query) use ($identifier) {
                $query->where('email', $identifier)
                    ->orWhere('phone', $identifier);
            })
            ->first();

        if ($user && $user->profile) {
            $profile = $user->profile;
            if ($profile->doc_number === $document || $profile->passport_number === $document) {
                Filament::auth()->login($user, $data['remember'] ?? false);
                session()->regenerate();

                return app(LoginResponse::class);
            }
        }

        throw ValidationException::withMessages([
            'data.identifier' => 'Las credenciales proporcionadas no son correctas.',
        ]);
    }
}
