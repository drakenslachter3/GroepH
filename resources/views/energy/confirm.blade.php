<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800 leading-tight">
            {{ __('Stel je jaarlijkse budget in') }}
        </h2>
    </x-slot>

    <div class="container mx-auto mt-6">
        <!-- Budget Information Cards -->
        <div class="grid gap-6 md:grid-cols-2">
            <!-- Gas Budget Card -->
            <div class="bg-white shadow-lg rounded-lg p-6">
                <p class="text-sm text-gray-500">Huidige gasprijs: €{{ number_format($energyService->gasRate, 2) }} per m³</p>
                <h3 class="text-lg font-medium text-gray-700 mt-2">Gas Budget</h3>
                <p class="text-gray-900">€{{ number_format($calculations['gas_euro'], 2) }} ({{ number_format($calculations['gas_m3'], 2) }} m³)</p>
            </div>
            <!-- Electricity Budget Card -->
            <div class="bg-white shadow-lg rounded-lg p-6">
                <p class="text-sm text-gray-500">Huidige elektriciteitsprijs: €{{ number_format($energyService->electricityRate, 2) }} per kWh</p>
                <h3 class="text-lg font-medium text-gray-700 mt-2">Electricity Budget</h3>
                <p class="text-gray-900">€{{ number_format($calculations['electricity_euro'], 2) }} ({{ number_format($calculations['electricity_kwh'], 2) }} kWh)</p>
            </div>
        </div>

        <!-- Form Section -->
        <div class="bg-white shadow-lg rounded-lg p-6 mt-6">
            @if(session('success'))
                <div class="mb-4 text-sm text-green-600">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('budget.store') }}">
                @csrf

                <input type="hidden" name="gas_m3" value="{{ $calculations['gas_m3'] }}">
                <input type="hidden" name="gas_euro" value="{{ $calculations['gas_euro'] }}">
                <input type="hidden" name="electricity_kwh" value="{{ $calculations['electricity_kwh'] }}">
                <input type="hidden" name="electricity_euro" value="{{ $calculations['electricity_euro'] }}">

                <div class="flex gap-4 mt-4">
                    <button type="submit" class="px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700">Bereken en opslaan</button>
                    <a href="{{ route('budget.form') }}" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">Terug</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
