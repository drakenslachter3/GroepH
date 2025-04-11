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

    public function store(Request $request)
    {
        $request->validate([
            'electricity_value' => 'required|numeric|min:0',
            'gas_value' => 'required|numeric|min:0',
        ]);
        
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        
        $user = Auth::user();
        $currentYear = date('Y');
        
        // Calculate euro values
        $electricityEuro = $this->conversionService->kwhToEuro($request->electricity_value);
        $gasEuro = $this->conversionService->m3ToEuro($request->gas_value);
        
        // Create or update the yearly budget
        $budget = EnergyBudget::updateOrCreate(
            [
                'user_id' => $user->id,
                'year' => $currentYear
            ],
            [
                'electricity_target_kwh' => $request->electricity_value,
                'electricity_target_euro' => $electricityEuro,
                'gas_target_m3' => $request->gas_value,
                'gas_target_euro' => $gasEuro,
            ]
        );
        
        // Create default monthly budgets
        $this->createOrUpdateMonthlyBudgets($budget);
        
        return redirect()->route('budget.form')->with('success', 'Jaarbudget succesvol opgeslagen!');
    }
    
    public function updateMonthly(Request $request)
    {
        $validated = $request->validate([
            'budgets' => 'required|array',
            'budgets.*.month' => 'required|integer|min:1|max:12',
            'budgets.*.gas_target_m3' => 'required|numeric|min:0',
            'budgets.*.electricity_target_kwh' => 'required|numeric|min:0',
        ]);
        
        $user = Auth::user();
        $currentYear = date('Y');
        
        // Get or create the yearly budget
        $yearlyBudget = EnergyBudget::where('user_id', $user->id)
            ->where('year', $currentYear)
            ->latest()
            ->first();
            
        if (!$yearlyBudget) {
            return redirect()->route('budget.form')
                ->with('error', 'Jaarbudget niet gevonden. Stel eerst een jaarbudget in.');
        }
        
        // Process each monthly budget
        foreach ($validated['budgets'] as $budgetData) {
            MonthlyEnergyBudget::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'energy_budget_id' => $yearlyBudget->id,
                    'month' => $budgetData['month']
                ],
                [
                    'gas_target_m3' => $budgetData['gas_target_m3'],
                    'electricity_target_kwh' => $budgetData['electricity_target_kwh'],
                    'is_default' => false
                ]
            );
        }
        
        // Update yearly totals based on the sum of monthly values
        $monthlyBudgets = MonthlyEnergyBudget::where('user_id', $user->id)
            ->where('energy_budget_id', $yearlyBudget->id)
            ->get();
            
        $totalGas = $monthlyBudgets->sum('gas_target_m3');
        $totalElectricity = $monthlyBudgets->sum('electricity_target_kwh');
        
        $yearlyBudget->update([
            'gas_target_m3' => $totalGas,
            'gas_target_euro' => $this->conversionService->m3ToEuro($totalGas),
            'electricity_target_kwh' => $totalElectricity,
            'electricity_target_euro' => $this->conversionService->kwhToEuro($totalElectricity)
        ]);
        
        return redirect()->route('budget.form')
            ->with('success', 'Maandbudgetten succesvol bijgewerkt!');
    }
    
    protected function createOrUpdateMonthlyBudgets(EnergyBudget $yearlyBudget)
    {
        $user = Auth::user();
        
        // Check if monthly budgets already exist
        $existingBudgets = MonthlyEnergyBudget::where('user_id', $user->id)
            ->where('energy_budget_id', $yearlyBudget->id)
            ->exists();
            
        if (!$existingBudgets) {
            // Create default distribution (even across all months)
            $monthlyGasValue = $yearlyBudget->gas_target_m3 / 12;
            $monthlyElectricityValue = $yearlyBudget->electricity_target_kwh / 12;
            
            for ($month = 1; $month <= 12; $month++) {
                MonthlyEnergyBudget::create([
                    'user_id' => $user->id,
                    'energy_budget_id' => $yearlyBudget->id,
                    'month' => $month,
                    'gas_target_m3' => $monthlyGasValue,
                    'electricity_target_kwh' => $monthlyElectricityValue,
                    'is_default' => true
                ]);
            }
        } else {
            // Update existing budgets proportionally
            $budgets = MonthlyEnergyBudget::where('user_id', $user->id)
                ->where('energy_budget_id', $yearlyBudget->id)
                ->get();
                
            $currentGasTotal = $budgets->sum('gas_target_m3');
            $currentElectricityTotal = $budgets->sum('electricity_target_kwh');
            
            if ($currentGasTotal > 0 && $currentElectricityTotal > 0) {
                $gasRatio = $yearlyBudget->gas_target_m3 / $currentGasTotal;
                $electricityRatio = $yearlyBudget->electricity_target_kwh / $currentElectricityTotal;
                
                foreach ($budgets as $budget) {
                    $budget->update([
                        'gas_target_m3' => $budget->gas_target_m3 * $gasRatio,
                        'electricity_target_kwh' => $budget->electricity_target_kwh * $electricityRatio
                    ]);
                }
            }
        }
    }
}