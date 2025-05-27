<div class="max-w-md mx-auto my-8 p-6 bg-white shadow-lg rounded-lg border border-gray-100">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">Widget Configuratie</h2>
    
    <form action="{{ route('dashboard.setWidget') }}" method="POST" class="space-y-6">
        @csrf
        <div class="space-y-2">
            <label for="grid-position" class="block text-sm font-medium text-gray-700">Positie:</label>
            <select name="grid_position" id="grid-position" class="w-full p-3 bg-gray-50 border border-gray-300 rounded-md text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200">
                @for ($i = 0; $i < count($gridLayout); $i++)
                    <option value="{{ $i }}">Positie {{ $i + 1 }}</option>
                @endfor
            </select>
        </div>
        
        <div class="space-y-2">
            <label for="widget-type" class="block text-sm font-medium text-gray-700">Widget Type:</label>
            <select name="widget_type" id="widget-type" class="w-full p-3 bg-gray-50 border border-gray-300 rounded-md text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200">
                <option value="date-selector">Datum en Periode Selectie</option>
                <option value="usage-prediction">Voorspelling en Prognose</option>
                <option value="energy-status-electricity">Electra Status</option>
                <option value="energy-status-gas">Gas Status</option>
                <option value="historical-comparison">Historische Vergelijking</option>
                <option value="net-result">Netto resultante</option>
                <option value="energy-chart">Energie Grafiek</option>
                <option value="trend-analysis">Trend Analyse</option>
                <option value="energy-suggestions">Energiebesparingstips</option>
                <option value="budget-alert">Budget Waarschuwing</option>
                <option value="energy-prediction-chart-electricity">Elektriciteit Voorspelling Grafiek</option>
                <option value="energy-prediction-chart-gas">Gas Voorspelling Grafiek</option>
            </select>
        </div>
        
        <button type="submit" class="w-full py-3 px-4 bg-green-600 hover:bg-green-700 text-white font-medium rounded-md shadow-sm transition duration-200 focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-offset-2">
            Widget Toevoegen
        </button>
    </form>
    
    <div class="mt-6 border-t border-gray-200 pt-6 flex space-x-4">
        <form action="{{ route('dashboard.resetLayout') }}" method="POST" class="flex-1">
            @csrf
            <button type="submit" class="w-full py-2 px-4 bg-red-500 hover:bg-red-600 text-white font-medium rounded-md shadow-sm transition duration-200 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2">
                Reset Layout
            </button>
        </form>
        
        <button onclick="window.location.href='{{ route('budget.form') }}'" class="flex-1 py-2 px-4 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-md shadow-sm transition duration-200 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2">
            Budget Aanpassen
        </button>
    </div>
</div>