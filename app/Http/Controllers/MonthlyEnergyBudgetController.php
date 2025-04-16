<?php
namespace App\Http\Controllers;

use App\Models\EnergyBudget;
use App\Models\MonthlyEnergyBudget;
use App\Services\EnergyConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MonthlyEnergyBudgetController extends Controller
{
    private $conversionService;

    public function __construct(EnergyConversionService $conversionService)
    {
        $this->conversionService = $conversionService;
    }

    public function index()
    {
        $user         = Auth::user();
        $yearlyBudget = EnergyBudget::where('user_id', $user->id)
            ->where('year', date('Y'))
            ->latest()
            ->first();

        if (! $yearlyBudget) {
            return redirect()->route('budget.form')->with('error', 'Stel eerst een jaarlijks budget in.');
        }

        $monthlyBudgets = MonthlyEnergyBudget::where('user_id', $user->id)
            ->where('energy_budget_id', $yearlyBudget->id)
            ->orderBy('month')
            ->get();

        // If no monthly budgets exist, create default ones
        if ($monthlyBudgets->isEmpty()) {
            $this->createDefaultMonthlyBudgets($yearlyBudget);
            $monthlyBudgets = MonthlyEnergyBudget::where('user_id', $user->id)
                ->where('energy_budget_id', $yearlyBudget->id)
                ->orderBy('month')
                ->get();
        }

        return view('energy.monthly-budget', [
            'yearlyBudget'      => $yearlyBudget,
            'monthlyBudgets'    => $monthlyBudgets,
            'conversionService' => $this->conversionService,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'budgets'                          => 'required|array',
            'budgets.*.id'                     => 'sometimes|exists:monthly_energy_budgets,id',
            'budgets.*.month'                  => 'required|integer|min:1|max:12',
            'budgets.*.gas_target_m3'          => 'required|numeric|min:0',
            'budgets.*.electricity_target_kwh' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();

        foreach ($validated['budgets'] as $budgetData) {
            if (isset($budgetData['id'])) {
                $budget = MonthlyEnergyBudget::findOrFail($budgetData['id']);

                // Verify the budget belongs to the user
                if ($budget->user_id !== $user->id) {
                    continue;
                }

                $budget->update([
                    'gas_target_m3'          => $budgetData['gas_target_m3'],
                    'electricity_target_kwh' => $budgetData['electricity_target_kwh'],
                    'is_default'             => false,
                ]);
            }
        }

        return redirect()->route('budget.monthly')->with('success', 'Maandelijkse budgetten bijgewerkt.');
    }

    public function reset(Request $request)
    {
        $user         = Auth::user();
        $yearlyBudget = EnergyBudget::where('user_id', $user->id)
            ->where('year', date('Y'))
            ->latest()
            ->first();

        if (! $yearlyBudget) {
            return redirect()->route('budget.form')->with('error', 'Geen jaarlijks budget gevonden.');
        }

        // Delete existing monthly budgets
        MonthlyEnergyBudget::where('user_id', $user->id)
            ->where('energy_budget_id', $yearlyBudget->id)
            ->delete();

        // Create default budgets
        $this->createDefaultMonthlyBudgets($yearlyBudget);

        return redirect()->route('budget.monthly')->with('success', 'Maandelijkse budgetten gereset naar standaardwaarden.');
    }

    public function createDefaultMonthlyBudgets(EnergyBudget $yearlyBudget)
    {
        $user_id                   = $yearlyBudget->user_id;
        $monthlyGasFactors         = [1.8, 1.7, 1.4, 1.0, 0.7, 0.5, 0.4, 0.4, 0.6, 1.0, 1.5, 1.8]; // Seasonal factors
        $monthlyElectricityFactors = [1.1, 1.0, 0.9, 0.9, 0.9, 1.0, 1.1, 1.1, 0.9, 1.0, 1.0, 1.1]; // Seasonal factors

        $gasFactorSum         = array_sum($monthlyGasFactors);
        $electricityFactorSum = array_sum($monthlyElectricityFactors);

        for ($month = 1; $month <= 12; $month++) {
            $index                       = $month - 1;
            $normalizedGasFactor         = $monthlyGasFactors[$index] * 12 / $gasFactorSum;
            $normalizedElectricityFactor = $monthlyElectricityFactors[$index] * 12 / $electricityFactorSum;

            $gasTarget         = round(($yearlyBudget->gas_target_m3 / 12) * $normalizedGasFactor, 2);
            $electricityTarget = round(($yearlyBudget->electricity_target_kwh / 12) * $normalizedElectricityFactor, 2);

            MonthlyEnergyBudget::create([
                'user_id'                => $user_id,
                'energy_budget_id'       => $yearlyBudget->id,
                'month'                  => $month,
                'gas_target_m3'          => $gasTarget,
                'electricity_target_kwh' => $electricityTarget,
                'is_default'             => true,
            ]);
        }
    }
}
