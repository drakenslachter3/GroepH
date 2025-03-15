<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Stel je jaarlijkse budget in') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h2 class="text-2xl font-semibold">Stel je jaarlijkse budget in</h2>

                    @php
                        $energyService = new App\Services\EnergyConversionService();
                    @endphp

                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('budget.calculate') }}" class="mt-4">
                        @csrf

                        <!-- Gas Budget Section -->
                        <div class="mb-3">
                            <label class="form-label">Budget Gas</label>
                            <p class="text-muted small">
                                Huidige gasprijs: €{{ number_format($energyService->gasRate, 2) }} per m³
                            </p>
                            <div class="input-group">
                                <input type="number"
                                       step="0.01"
                                       name="gas_value"
                                       class="form-control"
                                       value="{{ old('gas_value') }}"
                                       required>
                                <select name="gas_unit" class="form-select">
                                    <option value="euro" selected>€</option>
                                    <option value="m3">m³</option>
                                </select>
                            </div>
                        </div>

                        <!-- Electricity Budget Section -->
                        <div class="mb-3">
                            <label class="form-label">Budget Electra</label>
                            <p class="text-muted small">
                                Huidige elektriciteitsprijs: €{{ number_format($energyService->electricityRate, 2) }} per kWh
                            </p>
                            <div class="input-group">
                                <input type="number"
                                       step="0.01"
                                       name="electricity_value"
                                       class="form-control"
                                       value="{{ old('electricity_value') }}"
                                       required>
                                <select name="electricity_unit" class="form-select">
                                    <option value="euro" selected>€</option>
                                    <option value="kwh">kWh</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Bereken</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
