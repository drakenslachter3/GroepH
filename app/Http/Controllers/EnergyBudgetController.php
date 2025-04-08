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
            'gas_value'         => 'required|numeric|min:0',
            'gas_unit'          => 'required|in:m3,euro',
            'electricity_value' => 'required|numeric|min:0',
            'electricity_unit'  => 'required|in:kwh,euro',
        ]);

        $calculations = [
            'gas_m3'           => $validated['gas_unit'] === 'm3'
            ? $validated['gas_value']
            : $this->conversionService->euroToM3($validated['gas_value']),
            'gas_euro'         => $validated['gas_unit'] === 'euro'
            ? $validated['gas_value']
            : $this->conversionService->m3ToEuro($validated['gas_value']),
            'electricity_kwh'  => $validated['electricity_unit'] === 'kwh'
            ? $validated['electricity_value']
            : $this->conversionService->euroToKwh($validated['electricity_value']),
            'electricity_euro' => $validated['electricity_unit'] === 'euro'
            ? $validated['electricity_value']
            : $this->conversionService->kwhToEuro($validated['electricity_value']),
        ];

        $energyService = $this->conversionService;
        return view('energy.confirm', compact('calculations', 'energyService'));
    }

    public function store(Request $request)
    {
        if (! Auth::check()) {
            return view('/register');
        }
        $user_id = Auth::user()->id;
        $budget  = EnergyBudget::create([
            'gas_target_m3'           => $request->gas_m3,
            'gas_target_euro'         => $request->gas_euro,
            'electricity_target_kwh'  => $request->electricity_kwh,
            'electricity_target_euro' => $request->electricity_euro,
            'year'                    => date('Y'),
            'user_id'                 => $user_id,
        ]);

        // Create monthly budgets with default distribution
        $this->createDefaultMonthlyBudgets($budget);

        return redirect()->route('budget.form')->with('success', 'Opgeslagen!');
    }

    private function createDefaultMonthlyBudgets(EnergyBudget $budget)
    {
        $monthlyBudgetController = app(MonthlyEnergyBudgetController::class);
        $monthlyBudgetController->createDefaultMonthlyBudgets($budget);
    }
}
