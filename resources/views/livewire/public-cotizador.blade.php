<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-cover bg-center" style="background-image: url('{{ asset('images/landing/hero-bg.jpg') }}');">
    <div class="absolute inset-0 bg-gray-900 bg-opacity-75"></div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
            Armá tu próximo viaje
        </h2>
        <p class="mt-2 text-center text-sm text-gray-300">
            Completá los datos y te enviamos una cotización a medida.
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md relative z-10">
        <div class="bg-white py-8 px-4 shadow-2xl sm:rounded-2xl sm:px-10 border border-gray-100">
            @if($isSubmitted)
                <div class="text-center py-8">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                        <svg class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">¡Recibimos tu solicitud!</h3>
                    <p class="text-gray-600 mb-6">Uno de nuestros agentes expertos revisará tu pedido y te contactará a la brevedad con la mejor propuesta.</p>
                    <a href="/" class="text-primary-600 hover:text-primary-500 font-medium transition-colors">Volver al inicio</a>
                </div>
            @else
                <!-- Progress Bar -->
                <div class="mb-8">
                    <div class="flex items-center justify-between relative">
                        <div class="absolute left-0 top-1/2 transform -translate-y-1/2 w-full h-1 bg-gray-200 rounded"></div>
                        <div class="absolute left-0 top-1/2 transform -translate-y-1/2 h-1 bg-primary-600 rounded transition-all duration-300" style="width: {{ ($step - 1) * 50 }}%"></div>
                        
                        <div class="relative z-10 flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm {{ $step >= 1 ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500' }}">1</div>
                            <span class="text-xs mt-1 font-medium {{ $step >= 1 ? 'text-primary-600' : 'text-gray-400' }}">Destino</span>
                        </div>
                        <div class="relative z-10 flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm {{ $step >= 2 ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500' }}">2</div>
                            <span class="text-xs mt-1 font-medium {{ $step >= 2 ? 'text-primary-600' : 'text-gray-400' }}">Detalles</span>
                        </div>
                        <div class="relative z-10 flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm {{ $step >= 3 ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500' }}">3</div>
                            <span class="text-xs mt-1 font-medium {{ $step >= 3 ? 'text-primary-600' : 'text-gray-400' }}">Contacto</span>
                        </div>
                    </div>
                </div>

                <form wire:submit.prevent="submit" class="space-y-6">
                    
                    <!-- STEP 1: Destino -->
                    <div class="{{ $step === 1 ? 'block' : 'hidden' }}">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">¿A dónde te gustaría ir?</h3>
                        
                        <div class="mb-4">
                            <label for="destination" class="block text-sm font-medium text-gray-700">Destino</label>
                            <div class="mt-1">
                                <input wire:model="destination" id="destination" type="text" placeholder="Ej: Cancún, Europa, Patagonia..." class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>
                            @error('destination') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de viaje</label>
                            <div class="space-y-2">
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ $trip_type === 'vuelo_hotel' ? 'border-primary-500 bg-primary-50' : 'border-gray-200' }}">
                                    <input wire:model="trip_type" type="radio" value="vuelo_hotel" class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500">
                                    <span class="ml-3 block text-sm font-medium text-gray-900">Vuelo + Hotel</span>
                                </label>
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ $trip_type === 'paquete' ? 'border-primary-500 bg-primary-50' : 'border-gray-200' }}">
                                    <input wire:model="trip_type" type="radio" value="paquete" class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500">
                                    <span class="ml-3 block text-sm font-medium text-gray-900">Paquete (Circuito/Tour)</span>
                                </label>
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ $trip_type === 'solo_hotel' ? 'border-primary-500 bg-primary-50' : 'border-gray-200' }}">
                                    <input wire:model="trip_type" type="radio" value="solo_hotel" class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500">
                                    <span class="ml-3 block text-sm font-medium text-gray-900">Solo Hotel</span>
                                </label>
                            </div>
                            @error('trip_type') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="button" wire:click="nextStep" class="flex justify-center py-3 px-6 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                                Siguiente paso
                            </button>
                        </div>
                    </div>

                    <!-- STEP 2: Fechas y Pasajeros -->
                    <div class="{{ $step === 2 ? 'block' : 'hidden' }}">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">¿Cuándo y con quién viajás?</h3>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="travel_date_start" class="block text-sm font-medium text-gray-700">Fecha ida</label>
                                <div class="mt-1">
                                    <input wire:model="travel_date_start" id="travel_date_start" type="date" class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                </div>
                                @error('travel_date_start') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="travel_date_end" class="block text-sm font-medium text-gray-700">Fecha vuelta</label>
                                <div class="mt-1">
                                    <input wire:model="travel_date_end" id="travel_date_end" type="date" class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                </div>
                                @error('travel_date_end') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="adults" class="block text-sm font-medium text-gray-700">Adultos</label>
                                <div class="mt-1">
                                    <input wire:model="adults" id="adults" type="number" min="1" class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                </div>
                                @error('adults') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="children" class="block text-sm font-medium text-gray-700">Niños (0-11)</label>
                                <div class="mt-1">
                                    <input wire:model="children" id="children" type="number" min="0" class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                </div>
                                @error('children') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mt-6 flex justify-between">
                            <button type="button" wire:click="previousStep" class="flex justify-center py-3 px-6 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                                Volver
                            </button>
                            <button type="button" wire:click="nextStep" class="flex justify-center py-3 px-6 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                                Siguiente paso
                            </button>
                        </div>
                    </div>

                    <!-- STEP 3: Contacto -->
                    <div class="{{ $step === 3 ? 'block' : 'hidden' }}">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Por último, ¿cómo te contactamos?</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Nombre completo</label>
                                <div class="mt-1">
                                    <input wire:model="name" id="name" type="text" class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                </div>
                                @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <div class="mt-1">
                                    <input wire:model="email" id="email" type="email" class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                </div>
                                @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">WhatsApp</label>
                                <div class="mt-1">
                                    <input wire:model="phone" id="phone" type="text" placeholder="+54 9 11..." class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                </div>
                                @error('phone') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mt-8 flex justify-between">
                            <button type="button" wire:click="previousStep" class="flex justify-center py-3 px-6 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                                Volver
                            </button>
                            <button type="submit" class="flex justify-center py-3 px-6 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                                Solicitar Cotización
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
