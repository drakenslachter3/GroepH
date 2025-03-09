<div class="container mt-5">
    <h2>Stel je jaarlijkse budget in</h2>
    
    
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
        <div class="mb-3">
            <label class="form-label">Budget Gas</label>
            <p class="text-muted small">Huidige gasprijs: €{{ number_format($energyService->gasRate, 2) }} per m³</p>
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
        <br/>
        <div class="mb-3">
            <label class="form-label">Budget Electra</label>
            <p class="text-muted small">Huidige elektriciteitsprijs: €{{ number_format($energyService->electricityRate, 2) }} per kWh</p>
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
