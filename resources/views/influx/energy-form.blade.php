<!-- resources/views/influx/energy-form.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Energiegegevens ophalen uit InfluxDB') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="p-4 mb-4 text-green-700 bg-green-100 border border-green-200 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 mb-4 text-red-700 bg-red-100 border border-red-200 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form action="{{ route('energy.store-data') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="meter_id" class="block mb-2 text-sm font-medium text-gray-700">
                                Slimme Meter
                            </label>
                            <select id="meter_id" name="meter_id"
                                class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                @foreach ($smartMeters as $meter)
                                    <option value="{{ $meter->meter_id }}">{{ $meter->meter_id }}
                                        ({{ $meter->location }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="period" class="block mb-2 text-sm font-medium text-gray-700">
                                Periode
                            </label>
                            <select id="period" name="period"
                                class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="day">Dag</option>
                                <option value="month">Maand</option>
                                <option value="year">Jaar</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="date" class="block mb-2 text-sm font-medium text-gray-700">
                                Datum
                            </label>
                            <input type="date" id="date" name="date"
                                class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                value="{{ date('Y-m-d') }}">
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-600">
                                Gegevens ophalen en opslaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
