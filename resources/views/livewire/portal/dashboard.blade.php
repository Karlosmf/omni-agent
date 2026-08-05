<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Booking;
use App\Models\BookingPassenger;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.guest')]
new class extends Component {
    use WithFileUploads;

    public $bookings = [];
    public $user = null;
    
    // Uploads bucket
    public $uploads = [];

    public function mount()
    {
        $this->user = Auth::user();
        $this->loadBookings();
    }
    
    public function loadBookings()
    {
        $this->bookings = Booking::where('customer_id', clone $this->user->id)
            ->with(['transactions' => function ($q) {
                $q->orderBy('payment_date', 'desc');
            }, 'items', 'itineraryDays', 'bookingPassengers'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function uploadPassport($passengerId)
    {
        $this->validate([
            "uploads.$passengerId" => 'required|image|max:5120',
        ]);
        
        $passenger = BookingPassenger::whereHas('booking', function ($q) {
            $q->where('customer_id', $this->user->id);
        })->find($passengerId);
        
        if ($passenger) {
            $path = $this->uploads[$passengerId]->store('passports', 'public');
            $passenger->update(['passport_path' => $path]);
            unset($this->uploads[$passengerId]);
            $this->loadBookings();
            session()->flash('success_upload_'.$passengerId, 'Documento subido con éxito.');
        }
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
}; ?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-8">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                    Hola, {{ $user->name }} 👋
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Acá podés revisar el estado de tus viajes y los pagos realizados.
                </p>
            </div>
            <div class="mt-4 flex md:ml-4 md:mt-0">
                <button wire:click="logout" type="button" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Cerrar Sesión
                </button>
            </div>
        </div>

        @if($bookings->isEmpty())
            <div class="text-center py-12 bg-white rounded-lg shadow border border-gray-100">
                <x-filament::icon icon="heroicon-o-briefcase" class="mx-auto h-12 w-12 text-gray-300" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No hay viajes</h3>
                <p class="mt-1 text-sm text-gray-500">Todavía no tenés viajes asociados a tu cuenta.</p>
            </div>
        @else
            <div class="space-y-8">
                @foreach($bookings as $booking)
                    <div class="bg-white shadow rounded-lg overflow-hidden border border-gray-200">
                        <!-- Booking Header -->
                        <div class="bg-[var(--fe-primary)] px-4 py-5 sm:px-6 flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-medium leading-6 text-white">
                                    {{ $booking->destination ?: 'Viaje sin destino especificado' }}
                                </h3>
                                <p class="mt-1 max-w-2xl text-sm text-[var(--fe-primary-100)] opacity-90">
                                    File: {{ $booking->file_number }} 
                                    @if($booking->travel_date)
                                        &bull; Viaje: {{ $booking->travel_date->format('d/m/Y') }}
                                    @endif
                                </p>
                            </div>
                            <div>
                                <span class="inline-flex items-center rounded-full bg-white/20 px-2.5 py-0.5 text-xs font-medium text-white ring-1 ring-inset ring-white/20">
                                    {{ $booking->status->getLabel() }}
                                </span>
                            </div>
                        </div>

                        <!-- Booking Content -->
                        <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                
                                <!-- Summary -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 mb-4 uppercase tracking-wider">Resumen</h4>
                                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Total del Viaje</dt>
                                            <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $booking->currency }} {{ number_format($booking->total_sell, 2) }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Pagado</dt>
                                            @php
                                                $paid = $booking->transactions->where('type', App\Enums\TransactionType::Income)->where('status', App\Enums\TransactionStatus::Completed)->sum('amount');
                                            @endphp
                                            <dd class="mt-1 text-sm font-semibold text-green-600">{{ $booking->currency }} {{ number_format($paid, 2) }}</dd>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <dt class="text-sm font-medium text-gray-500">Saldo Pendiente</dt>
                                            <dd class="mt-1 text-sm font-bold text-gray-900">{{ $booking->currency }} {{ number_format($booking->total_sell - $paid, 2) }}</dd>
                                        </div>
                                    </dl>
                                    
                                    <div class="mt-6">
                                        <a href="{{ $booking->publicUrl() }}" target="_blank" class="inline-flex items-center text-sm font-medium text-[var(--fe-primary)] hover:text-opacity-80">
                                            Ver propuesta detallada <span aria-hidden="true">&rarr;</span>
                                        </a>
                                    </div>
                                </div>

                                <!-- Transactions -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 mb-4 uppercase tracking-wider">Historial de Pagos</h4>
                                    @if($booking->transactions->isEmpty())
                                        <p class="text-sm text-gray-500 italic">No hay pagos registrados aún.</p>
                                    @else
                                        <ul role="list" class="divide-y divide-gray-200">
                                            @foreach($booking->transactions->where('type', App\Enums\TransactionType::Income) as $tx)
                                                <li class="py-3 flex justify-between items-center">
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900">Pago {{ $tx->payment_method?->getLabel() ?? 'N/A' }}</p>
                                                        <p class="text-xs text-gray-500">{{ $tx->payment_date?->format('d/m/Y') ?? $tx->created_at->format('d/m/Y') }} - {{ $tx->status->getLabel() }}</p>
                                                    </div>
                                                    <div class="text-right">
                                                        <p class="text-sm font-bold text-green-600">{{ $tx->currency }} {{ number_format($tx->amount, 2) }}</p>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Itinerary -->
                            @if($booking->itineraryDays && $booking->itineraryDays->isNotEmpty())
                                <div class="mt-8 pt-8 border-t border-gray-200">
                                    <h4 class="text-sm font-medium text-gray-900 mb-6 uppercase tracking-wider">Itinerario del Viaje</h4>
                                    <div class="relative border-l-2 border-gray-100 ml-3 space-y-6">
                                        @foreach($booking->itineraryDays as $day)
                                            <div class="relative pl-6">
                                                <div class="absolute -left-[5px] top-1.5 w-2 h-2 rounded-full bg-[var(--fe-primary)] border-2 border-white box-content"></div>
                                                <div class="bg-gray-50 rounded-xl p-4 sm:p-5 border border-gray-100">
                                                    <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
                                                        <h5 class="text-base font-bold text-gray-900"><span class="text-[var(--fe-primary)] mr-1">Día {{ $day->day_number }}:</span> {{ $day->title }}</h5>
                                                        @if($day->date || $day->location)
                                                            <div class="text-xs font-medium text-gray-500">
                                                                @if($day->date) {{ $day->date->format('d/m/Y') }} @endif
                                                                @if($day->date && $day->location) &bull; @endif
                                                                @if($day->location) {{ $day->location }} @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                    @if($day->description)
                                                        <div class="text-sm text-gray-600 mb-3">
                                                            {!! nl2br(e($day->description)) !!}
                                                        </div>
                                                    @endif
                                                    @if(is_array($day->services) && count($day->services) > 0)
                                                        <div class="mt-3 pt-3 border-t border-gray-200/60">
                                                            <ul class="flex flex-wrap gap-2">
                                                                @foreach($day->services as $service)
                                                                    <li class="inline-flex items-center rounded-md bg-white px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-200 shadow-sm">
                                                                        {{ $service['description'] ?? 'Servicio' }}
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Vouchers -->
                            @if(is_array($booking->vouchers) && count($booking->vouchers) > 0)
                                <div class="mt-8 pt-8 border-t border-gray-200">
                                    <h4 class="text-sm font-medium text-gray-900 mb-6 uppercase tracking-wider">Vouchers y Documentos</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        @foreach($booking->vouchers as $voucher)
                                            <a href="{{ Storage::url($voucher) }}" target="_blank" class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl border border-gray-200 hover:bg-gray-100 transition-colors">
                                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                                <span class="text-sm font-medium text-[var(--fe-primary)] truncate">{{ basename($voucher) }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Passengers (Upload Passports) -->
                            @if($booking->bookingPassengers && $booking->bookingPassengers->isNotEmpty())
                                <div class="mt-8 pt-8 border-t border-gray-200">
                                    <h4 class="text-sm font-medium text-gray-900 mb-6 uppercase tracking-wider">Pasajeros y Documentación</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        @foreach($booking->bookingPassengers as $passenger)
                                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 flex flex-col justify-between">
                                                <div>
                                                    <p class="font-semibold text-gray-900">{{ $passenger->first_name }} {{ $passenger->last_name }}</p>
                                                    <p class="text-xs text-gray-500">{{ $passenger->document_type }}: {{ $passenger->document_number }}</p>
                                                </div>
                                                
                                                <div class="mt-4">
                                                    @if($passenger->passport_path)
                                                        <a href="{{ Storage::url($passenger->passport_path) }}" target="_blank" class="inline-flex items-center text-xs font-medium text-green-600 bg-green-50 px-2.5 py-1 rounded-md ring-1 ring-inset ring-green-600/20">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                            Documento Subido
                                                        </a>
                                                    @else
                                                        <form wire:submit="uploadPassport({{ $passenger->id }})" class="flex items-center gap-2">
                                                            <input type="file" wire:model="uploads.{{ $passenger->id }}" class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-[var(--fe-primary-50)] file:text-[var(--fe-primary)] hover:file:bg-[var(--fe-primary-100)]" accept="image/*,.pdf">
                                                            <button type="submit" class="inline-flex items-center rounded-md bg-[var(--fe-primary)] px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-[var(--fe-primary-600)] disabled:opacity-50" wire:loading.attr="disabled" wire:target="uploads.{{ $passenger->id }}">Subir</button>
                                                        </form>
                                                        @error('uploads.'.$passenger->id) <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                                                        @if(session()->has('success_upload_'.$passenger->id))
                                                            <span class="text-xs text-green-500 mt-1 block">{{ session('success_upload_'.$passenger->id) }}</span>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>
