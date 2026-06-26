<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Public Directory Status
        </x-slot>

        <x-slot name="description">
            Verification of necessary folders for asset uploads due to hosting restrictions.
        </x-slot>

        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900">
            <table class="w-full text-left divide-y divide-gray-200 dark:divide-white/10">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 dark:text-gray-300">Directory</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 dark:text-gray-300">Path</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 dark:text-gray-300">Status</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 dark:text-gray-300">Writable</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 dark:text-gray-300">Permissions</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 dark:text-gray-300">Owner</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach($this->directories as $dir)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/5 transition duration-200">
                            <td class="px-6 py-5 text-base font-medium text-gray-900 dark:text-white">{{ $dir['name'] }}</td>
                            <td class="px-6 py-5 text-sm text-gray-500 dark:text-gray-400 font-mono">{{ $dir['path'] }}</td>
                            <td class="px-6 py-5 text-sm">
                                @if($dir['exists'])
                                    <x-filament::badge color="success" size="lg">Exists</x-filament::badge>
                                @else
                                    <x-filament::badge color="danger" size="lg">Missing</x-filament::badge>
                                @endif
                            </td>
                            <td class="px-6 py-5 text-sm">
                                @if($dir['writable'])
                                    <x-filament::badge color="success" size="lg">Yes</x-filament::badge>
                                @else
                                    <x-filament::badge color="danger" size="lg">No</x-filament::badge>
                                @endif
                            </td>
                            <td class="px-6 py-5 text-sm text-gray-500 dark:text-gray-400 font-mono">
                                {{ $dir['permissions'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-5 text-sm text-gray-500 dark:text-gray-400 font-semibold">
                                {{ $dir['owner'] ?? 'N/A' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
