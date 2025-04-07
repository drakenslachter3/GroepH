<?php

namespace App\Http\Controllers;

use App\Models\EnergyBudget;
use App\Services\EnergyConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnergyBudgetController extends Controller
{
    private $conversionService;

    public function __construct(EnergyConversionService $conversionService)
    {
        $this->conversionService = $conversionService;
    }

    public function index()
    {
        return view('energy.form');
    }

    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'gas_value' => 'required|numeric|min:0',
            'gas_unit' => 'required|in:m3,euro',
            'electricity_value' => 'required|numeric|min:0',
            'electricity_unit' => 'required|in:kwh,euro',
        ]);

        $calculations = [
            'gas_m3' => $validated['gas_value'],
            'electricity_kwh' => $validated['electricity_value'],
        ];

        $energyService = $this->conversionService;
        return view('energy.confirm', compact('calculations', 'energyService'));
    }

    public function store(Request $request)
    {
        if(!Auth::check()){
            return view('/register');
        }
        $user_id = Auth::user()->id;
        $budget = EnergyBudget::create([
            'gas_target_m3' => $request->gas_m3,
            'electricity_target_kwh' => $request->electricity_kwh,
            'year' => date('Y'),
            'user_id' => $user_id,
        ]);

        return redirect()->route('budget.form')->with('success', 'Opgeslagen!');
    }
}
