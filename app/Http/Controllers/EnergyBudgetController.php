<?php
namespace App\Http\Controllers;

use App\Models\EnergyBudget;
use App\Models\MonthlyEnergyBudget;
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
        $user = Auth::user();
        $currentYear = date('Y');
        
        // Get current year's budget
        $yearlyBudget = EnergyBudget::where('user_id', $user->id)
            ->where('year', $currentYear)
            ->latest()
            ->first();
            
        // Get monthly budgets if yearly budget exists
        $monthlyBudgets = null;
        if ($yearlyBudget) {
            $monthlyBudgets = MonthlyEnergyBudget::where('user_id', $user->id)
                ->where('energy_budget_id', $yearlyBudget->id)
                ->orderBy('month')
                ->get();
        }
        
        return view('energy.form', [
            'yearlyBudget' => $yearlyBudget,
            'monthlyBudgets' => $monthlyBudgets
        ]);
    }

    public function calculate(Request $request)
    {
        // Validate input
        $request->validate([
            'electricity_value' => 'required|numeric|min:0',
            'gas_value' => 'required|numeric|min:0',
        ]);
        
        // Calculate euro values
        $electricityEuro = $this->conversionService->kwhToEuro($request->electricity_value);
        $gasEuro = $this->conversionService->m3ToEuro($request->gas_value);
        
        // Prepare calculation result
        $calculations = [
            'electricity_kwh' => (float) $request->electricity_value,
            'electricity_euro' => $electricityEuro,
            'gas_m3' => (float) $request->gas_value,
            'gas_euro' => $gasEuro,
        ];
        
        // Return result to confirmation view
        return view('energy.confirm', [
            'calculations' => $calculations,
            'energyService' => $this->conversionService,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'electricity_value' => 'required|numeric|min:0',
            'gas_value' => 'required|numeric|min:0',
            'budgets' => 'sometimes|array',
            'budgets.*.month' => 'sometimes|integer|min:1|max:12',
            'budgets.*.electricity_target_kwh' => 'sometimes|numeric|min:0',
            'budgets.*.gas_target_m3' => 'sometimes|numeric|min:0',
        ]);
        
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        
        $user = Auth::user();
        $currentYear = date('Y');
        
        // Calculate euro values
        $electricityValue = (float) $request->electricity_value;
        $gasValue = (float) $request->gas_value;
        $electricityEuro = $this->conversionService->kwhToEuro($electricityValue);
        $gasEuro = $this->conversionService->m3ToEuro($gasValue);
        
        // Create or update the yearly budget
        $budget = EnergyBudget::updateOrCreate(
            [
                'user_id' => $user->id,
                'year' => $currentYear
            ],
            [
                'electricity_target_kwh' => $electricityValue,
                'electricity_target_euro' => $electricityEuro,
                'gas_target_m3' => $gasValue,
                'gas_target_euro' => $gasEuro,
            ]
        );
        
        // Handle monthly budget data if provided
        if ($request->has('budgets')) {
            // Delete existing monthly budgets for this user and year
            MonthlyEnergyBudget::where('user_id', $user->id)
                ->where('energy_budget_id', $budget->id)
                ->delete();
                
            // Process each month's budget
            foreach ($request->budgets as $index => $monthData) {
                if (isset($monthData['month']) && isset($monthData['electricity_target_kwh']) && isset($monthData['gas_target_m3'])) {
                    MonthlyEnergyBudget::create([
                        'user_id' => $user->id,
                        'energy_budget_id' => $budget->id,
                        'month' => $monthData['month'],
                        'electricity_target_kwh' => (float) $monthData['electricity_target_kwh'],
                        'gas_target_m3' => (float) $monthData['gas_target_m3'],
                        'is_default' => false,
                    ]);
                }
            }
        } else {
            // If no monthly data provided, create default monthly budgets
            $this->createDefaultMonthlyBudgets($budget);
        }
        
        return redirect()->route('budget.form')
            ->with('success', 'Energiebudget succesvol opgeslagen!');
    }
    
    protected function createDefaultMonthlyBudgets(EnergyBudget $yearlyBudget)
    {
        $user_id = $yearlyBudget->user_id;
        
        // Define seasonal patterns (higher in winter, lower in summer)
        $monthlyGasFactors = [1.8, 1.7, 1.4, 1.0, 0.7, 0.5, 0.4, 0.4, 0.6, 1.0, 1.5, 1.8];
        $monthlyElectricityFactors = [1.1, 1.0, 0.9, 0.9, 0.9, 1.0, 1.1, 1.1, 0.9, 1.0, 1.0, 1.1];
        
        // Normalize factors to ensure they sum up to match the yearly total
        $gasFactorSum = array_sum($monthlyGasFactors);
        $electricityFactorSum = array_sum($monthlyElectricityFactors);
        
        // Create a budget for each month
        for ($month = 1; $month <= 12; $month++) {
            $index = $month - 1;
            $normalizedGasFactor = $monthlyGasFactors[$index] * 12 / $gasFactorSum;
            $normalizedElectricityFactor = $monthlyElectricityFactors[$index] * 12 / $electricityFactorSum;
            
            $gasTarget = round(($yearlyBudget->gas_target_m3 / 12) * $normalizedGasFactor, 2);
            $electricityTarget = round(($yearlyBudget->electricity_target_kwh / 12) * $normalizedElectricityFactor, 2);
            
            MonthlyEnergyBudget::create([
                'user_id' => $user_id,
                'energy_budget_id' => $yearlyBudget->id,
                'month' => $month,
                'gas_target_m3' => $gasTarget,
                'electricity_target_kwh' => $electricityTarget,
                'is_default' => true,
            ]);
        }
    }
}