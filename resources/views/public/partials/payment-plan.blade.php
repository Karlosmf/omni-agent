@php
    $planInstallments = $booking->installments;
    $balanceUsd = round($planInstallments->where('status', '!=', \App\Enums\InstallmentStatus::Cancelled)->sum(fn ($i) => $i->remaining()), 2);
    $source = $settings?->default_rate_source ?? \App\Enums\RateSource::Oficial;
    $arsRate = app(\App\Services\CurrencyService::class)->getDollarRate($source);
@endphp

@if ($planInstallments->isNotEmpty() && $booking->status !== \App\Enums\BookingStatus::Cancelado)
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Tu plan de pagos</h3>
            <p class="text-sm text-gray-500 mt-1">Valores en USD. Podés pagar en pesos a la cotización del día.</p>
        </div>

        <ul class="divide-y divide-gray-100">
            @foreach ($planInstallments as $installment)
                <li class="px-6 py-4 flex items-center justify-between gap-3">
                    <div>
                        <p class="font-semibold text-gray-900">{{ $installment->label }}</p>
                        <p class="text-xs text-gray-500">Vence el {{ $installment->due_date->format('d/m/Y') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gray-900">USD {{ number_format($installment->amount, 2, ',', '.') }}</p>
                        <span @class([
                            'inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-medium',
                            'bg-green-100 text-green-700' => $installment->status === \App\Enums\InstallmentStatus::Paid,
                            'bg-red-100 text-red-700' => $installment->status === \App\Enums\InstallmentStatus::Overdue,
                            'bg-amber-100 text-amber-700' => $installment->status === \App\Enums\InstallmentStatus::Partial,
                            'bg-gray-100 text-gray-600' => in_array($installment->status, [\App\Enums\InstallmentStatus::Pending, \App\Enums\InstallmentStatus::Cancelled], true),
                        ])>{{ $installment->status->getLabel() }}</span>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="p-6 bg-gray-50 space-y-1">
            <p class="flex justify-between text-sm"><span class="text-gray-600">Saldo pendiente</span><span class="font-bold text-gray-900">USD {{ number_format($balanceUsd, 2, ',', '.') }}</span></p>
            @if ($balanceUsd > 0 && $arsRate)
                <p class="flex justify-between text-xs text-gray-500"><span>Hoy equivale a ({{ $source->getLabel() }})</span><span>ARS {{ number_format($balanceUsd * $arsRate, 0, ',', '.') }}</span></p>
            @endif
            @if ($balanceUsd <= 0)
                <p class="text-sm font-medium text-green-700">¡Tu viaje está totalmente pago!</p>
            @endif
        </div>

        @if (filled($settings?->payment_instructions))
            <div class="p-6 border-t border-gray-100">
                <p class="text-sm font-semibold text-gray-900 mb-1">Cómo pagar</p>
                <p class="text-sm text-gray-600 whitespace-pre-line">{{ $settings->payment_instructions }}</p>
            </div>
        @endif
    </div>
@endif
