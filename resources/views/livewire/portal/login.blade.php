<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<?php

use function Livewire\Volt\layout;
use function Livewire\Volt\rules;
use function Livewire\Volt\state;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

layout('components.layouts.guest');

state([
    'identifier' => '',
    'document' => '',
]);

rules([
    'identifier' => 'required|string',
    'document' => 'required|string',
]);

$login = function () {
    $this->validate();

    $identifier = trim($this->identifier);
    $document = trim($this->document);

    // Buscar el usuario por email o teléfono, filtrando sólo los que tienen rol Customer
    $user = User::where('role', 'customer')
        ->where(function ($query) use ($identifier) {
            $query->where('email', $identifier)
                ->orWhere('phone', $identifier);
        })
        ->first();

    if ($user && $user->profile) {
        $profile = $user->profile;
        if ($profile->doc_number === $document || $profile->passport_number === $document) {
            Auth::login($user);
            return redirect()->intended(route('portal.dashboard'));
        }
    }

    $this->addError('identifier', 'Las credenciales proporcionadas no son correctas.');
};

?>

<div class="min-h-[80vh] flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Portal del Cliente
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Ingresá a tu cuenta para ver el estado de tu viaje
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 border border-gray-100">
            <form wire:submit="login" class="space-y-6">
                
                @if($errors->has('identifier'))
                    <div class="rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <x-filament::icon icon="heroicon-m-x-circle" class="h-5 w-5 text-red-400" />
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Error al iniciar sesión</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p>{{ $errors->first('identifier') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div>
                    <label for="identifier" class="block text-sm font-medium text-gray-700">
                        Email o Teléfono
                    </label>
                    <div class="mt-1">
                        <input wire:model="identifier" id="identifier" type="text" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    </div>
                </div>

                <div>
                    <label for="document" class="block text-sm font-medium text-gray-700">
                        DNI o Pasaporte
                    </label>
                    <div class="mt-1">
                        <input wire:model="document" id="document" type="password" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[var(--fe-primary)] hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--fe-primary)]">
                        Ingresar al Portal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
