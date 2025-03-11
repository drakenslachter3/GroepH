<div class="container mt-5">
    <div class="card mb-4">
        <div class="card-body">
            <h3>Gas Budget</h3>
            <p>€{{ number_format($calculations['gas_euro'], 2) }} ({{ number_format($calculations['gas_m3'], 2) }} m³)</p>

            <h3>Electricity Budget</h3>
            <p>€{{ number_format($calculations['electricity_euro'], 2) }} ({{ number_format($calculations['electricity_kwh'], 2) }} kWh)</p>
        </div>
    </div>

    <form method="POST" action="{{ route('budget.store') }}">
        @csrf
        <input type="hidden" name="gas_m3" value="{{ $calculations['gas_m3'] }}">
        <input type="hidden" name="gas_euro" value="{{ $calculations['gas_euro'] }}">
        <input type="hidden" name="electricity_kwh" value="{{ $calculations['electricity_kwh'] }}">
        <input type="hidden" name="electricity_euro" value="{{ $calculations['electricity_euro'] }}">
        
        <button type="submit" class="btn btn-success">Opslaan</button>
        <a href="{{ route('budget.form') }}" class="btn btn-secondary">Terug</a>
    </form>
</div>
