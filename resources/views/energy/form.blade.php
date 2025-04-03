<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Stel je jaarlijkse budget in') }}
        </h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow dark:shadow-none rounded-lg p-6">
                @php
                $energyService = new App\Services\EnergyConversionService();
                @endphp
                <div class="mb-4">
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition duration-200">
                        Terug naar dashboard
                    </a>
                </div>
                @if(session('success'))
                <div class="my-4 p-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
                @endif
                <form method="POST" action="{{ route('budget.calculate') }}" class="mt-4">
                    @csrf
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Budget Gas</label>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Huidige gasprijs: €{{ number_format($energyService->gasRate, 2) }} per m³</p>
                        <div class="flex max-w-xs">
                            <input type="number"
                                step="0.01"
                                name="gas_value"
                                class="p-2 border dark:border-gray-600 rounded-l w-2/3 dark:bg-gray-700 dark:text-gray-200"
                                value="{{ old('gas_value') }}"
                                required>
                            <select name="gas_unit" class="p-2 border dark:border-gray-600 border-l-0 rounded-r bg-gray-50 dark:bg-gray-700 dark:text-gray-200 w-1/3">
                                <option value="euro" selected>€</option>
                                <option value="m3">m³</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Budget Elektra</label>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Huidige elektriciteitsprijs: €{{ number_format($energyService->electricityRate, 2) }} per kWh</p>
                        <div class="flex max-w-xs">
                            <input type="number"
                                step="0.01"
                                name="electricity_value"
                                class="p-2 border dark:border-gray-600 rounded-l w-2/3 dark:bg-gray-700 dark:text-gray-200"
                                value="{{ old('electricity_value') }}"
                                required>
                            <select name="electricity_unit" class="p-2 border dark:border-gray-600 border-l-0 rounded-r bg-gray-50 dark:bg-gray-700 dark:text-gray-200 w-1/3">
                                <option value="euro" selected>€</option>
                                <option value="kwh">kWh</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex space-x-4">
                        <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700 transition duration-200">
                            Bereken en opslaan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>