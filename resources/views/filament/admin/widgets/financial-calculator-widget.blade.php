<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-calculator class="w-5 h-5 text-primary-500" />
                <span>Simulador Financiero (Cálculo de Neto)</span>
            </div>
        </x-slot>

        <div class="grid gap-6 md:grid-cols-2">
            <!-- Inputs -->
            <div class="space-y-4">
                <div class="grid gap-2">
                    <label
                        class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        Monto Bruto (Cobro)
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="number" wire:model.live.debounce.500ms="grossAmount"
                            placeholder="0.00" />
                    </x-filament::input.wrapper>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Imp. Banco (%)</label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="number" wire:model.live.debounce.500ms="taxBankPercent" />
                        </x-filament::input.wrapper>
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">IIBB (%)</label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="number" wire:model.live.debounce.500ms="taxIibbPercent" />
                        </x-filament::input.wrapper>
                    </div>
                    <div class="grid gap-2">
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Comisión Plataforma
                            (%)</label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="number" wire:model.live.debounce.500ms="platformFeePercent" />
                        </x-filament::input.wrapper>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Impuesto Banco:</span>
                    <span class="font-medium text-red-600 dark:text-red-400">-
                        ${{ number_format($taxBankAmount, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">IIBB:</span>
                    <span class="font-medium text-red-600 dark:text-red-400">-
                        ${{ number_format($taxIibbAmount, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Comisión Plataforma:</span>
                    <span class="font-medium text-red-600 dark:text-red-400">-
                        ${{ number_format($platformFeeAmount, 2) }}</span>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700 pt-3 flex justify-between items-center">
                    <span class="font-bold text-lg">Neto a Recibir:</span>
                    <span class="font-bold text-xl text-success-600 dark:text-success-400">
                        ${{ number_format($netAmount, 2) }}
                    </span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>