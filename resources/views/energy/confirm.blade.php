<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Jaarlijkse Budget') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <!-- Gas and Electricity Budget Section -->
                    <div class="container mt-5">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h3>Gas Budget</h3>
                                <p>€{{ number_format($calculations['gas_euro'], 2) }} ({{ number_format($calculations['gas_m3'], 2) }} m³)</p>

                                <h3>Electricity Budget</h3>
                                <p>€{{ number_format($calculations['electricity_euro'], 2) }} ({{ number_format($calculations['electricity_kwh'], 2) }} kWh)</p>
                            </div>
                        </div>

                        <!-- Form for Saving Budget -->
                        <form method="POST" action="{{ route('budget.store') }}">
                            @csrf
                            <input type="hidden" name="gas_m3" value="{{ $calculations['gas_m3'] }}">
                            <input type="hidden" name="gas_euro" value="{{ $calculations['gas_euro'] }}">
                            <input type="hidden" name="electricity_kwh" value="{{ $calculations['electricity_kwh'] }}">
                            <input type="hidden" name="electricity_euro" value="{{ $calculations['electricity_euro'] }}">

                            <button type="submit" class="btn btn-success">Opslaan</button>
                            <a href="{{ route('energy.form') }}" class="btn btn-secondary">Terug</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
