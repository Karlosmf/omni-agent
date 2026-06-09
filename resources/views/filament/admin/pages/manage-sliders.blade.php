<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header Section with Create Button -->
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Administra tus sliders JSON almacenados en <code class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded font-mono text-xs">public/sliders/</code>.
                </p>
            </div>
            <div>
                {{ $this->createAction }}
            </div>
        </div>

        <!-- Sliders Grid/List -->
        @if($this->getSliders()->isEmpty())
            <x-filament::section>
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <div class="p-3 bg-primary-50 dark:bg-primary-950/30 rounded-full text-primary-500 dark:text-primary-400 mb-4">
                        <x-filament::icon icon="heroicon-m-chart-bar" class="w-8 h-8" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">No hay sliders creados</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-sm">
                        Comienza creando tu primer slider JSON para poder utilizarlo en tus componentes Livewire.
                    </p>
                    <div class="mt-6">
                        <x-filament::button
                            wire:click="mountAction('create')"
                            icon="heroicon-m-plus"
                        >
                            Crear Slider
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        @else
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; align-items: stretch;">
                @foreach($this->getSliders() as $slider)
                    <x-filament::section class="h-full flex flex-col justify-between">
                        <x-slot name="heading">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span class="truncate block" title="{{ $slider->name }}">{{ $slider->name }}</span>
                                <x-filament::badge color="info">
                                    {{ count($slider->slides) }} {{ count($slider->slides) === 1 ? 'diapositiva' : 'diapositivas' }}
                                </x-filament::badge>
                            </div>
                        </x-slot>
                        
                        <x-slot name="description">
                            <span class="line-clamp-2" title="{{ $slider->description ?? 'Sin descripción' }}">{{ $slider->description ?? 'Sin descripción disponible.' }}</span>
                        </x-slot>

                        <div style="margin-top: 1rem;">
                            <!-- Livewire Code Snippet helper -->
                            <div style="padding: 0.75rem; background-color: rgba(156, 163, 175, 0.1); border-radius: 0.5rem; border: 1px solid rgba(156, 163, 175, 0.2);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="font-size: 0.75rem; font-weight: 500; opacity: 0.7;">Snippet Livewire</span>
                                    <button 
                                        type="button"
                                        x-on:click="window.navigator.clipboard.writeText('<livewire:slider name=\'{{ $slider->name }}\' />'); $tooltip('Copiado', { timeout: 1500 })"
                                        title="Copiar código"
                                        style="opacity: 0.5; transition: opacity 0.2s;"
                                        onmouseover="this.style.opacity='1'"
                                        onmouseout="this.style.opacity='0.5'"
                                    >
                                        <x-filament::icon icon="heroicon-m-clipboard-document" class="w-4 h-4" />
                                    </button>
                                </div>
                                <code style="font-size: 0.75rem; font-family: monospace; display: block; word-break: break-all; user-select: all; color: var(--primary-600);">
                                    &lt;livewire:slider name="{{ $slider->name }}" /&gt;
                                </code>
                            </div>

                            <!-- Footer Actions -->
                            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(156, 163, 175, 0.2);">
                                <x-filament::button
                                    type="button"
                                    x-on:click="$wire.mountAction('edit', { name: '{{ $slider->name }}' })"
                                    color="gray"
                                    size="sm"
                                    icon="heroicon-m-pencil-square"
                                >
                                    Editar
                                </x-filament::button>

                                <x-filament::button
                                    type="button"
                                    x-on:click="$wire.mountAction('delete', { name: '{{ $slider->name }}' })"
                                    color="danger"
                                    size="sm"
                                    icon="heroicon-m-trash"
                                >
                                    Eliminar
                                </x-filament::button>
                            </div>
                        </div>
                    </x-filament::section>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Modals for Actions -->
    <x-filament-actions::modals />
</x-filament-panels::page>
