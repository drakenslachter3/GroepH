<?php
namespace App\Http\Controllers;

use App\Models\EnergyBudget;
use App\Models\MonthlyEnergyBudget;
use App\Models\SmartMeter;
use App\Services\EnergyConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EnergyBudgetController extends Controller
{
    private $conversionService;

    public function __construct(EnergyConversionService $conversionService)
    {
        $this->conversionService = $conversionService;
    }

    /**
     * Show the budget form with per-meter setup
     */
    public function index()
    {
        $user = Auth::user();
        $currentYear = date('Y');
        
        // Get all smart meters for the current user
        $smartMeters = SmartMeter::where('account_id', $user->id)
            ->where('active', true)
            ->get();
        
        // Get existing budgets for all meters
        $existingBudgets = collect();
        if ($smartMeters->count() > 0) {
            $existingBudgets = EnergyBudget::where('user_id', $user->id)
                ->where('year', $currentYear)
                ->whereIn('smart_meter_id', $smartMeters->pluck('id'))
                ->with(['monthlyBudgets' => function($query) {
                    $query->orderBy('month');
                }])
                ->get();
        }
        
        return view('energy.form', [
            'smartMeters' => $smartMeters,
            'existingBudgets' => $existingBudgets,
            'currentYear' => $currentYear
        ]);
    }

    /**
     * Store budgets for multiple meters
     */
    public function storePerMeter(Request $request)
    {
        $user = Auth::user();
        $currentYear = date('Y');
        
        // Get user's smart meters
        $smartMeters = SmartMeter::where('account_id', $user->id)
            ->where('active', true)
            ->get();
        
        if ($smartMeters->isEmpty()) {
            return redirect()->route('budget.form')
                ->with('error', 'Geen actieve slimme meters gevonden.');
        }
        
        // Validate that we have data for all meters
        $validationRules = [];
        foreach ($smartMeters as $meter) {
            $meterKey = "meters.{$meter->id}";
            
            if ($meter->measures_electricity) {
                $validationRules["{$meterKey}.electricity_target_kwh"] = 'required|numeric|min:0';
            }
            
            if ($meter->measures_gas) {
                $validationRules["{$meterKey}.gas_target_m3"] = 'required|numeric|min:0';
            }
            
            // Validate monthly data if provided
            if ($request->has("{$meterKey}.monthly")) {
                for ($i = 0; $i < 12; $i++) {
                    $validationRules["{$meterKey}.monthly.{$i}.month"] = 'required|integer|min:1|max:12';
                    
                    if ($meter->measures_electricity) {
                        $validationRules["{$meterKey}.monthly.{$i}.electricity_target_kwh"] = 'required|numeric|min:0';
                    }
                    
                    if ($meter->measures_gas) {
                        $validationRules["{$meterKey}.monthly.{$i}.gas_target_m3"] = 'required|numeric|min:0';
                    }
                }
            }
        }
        
        $validated = $request->validate($validationRules);
        
        DB::beginTransaction();
        try {
            $savedBudgets = 0;
            
            foreach ($smartMeters as $meter) {
                $meterData = $validated['meters'][$meter->id] ?? null;
                
                if (!$meterData) {
                    continue;
                }
                
                // Calculate euro values
                $electricityEuro = 0;
                $gasEuro = 0;
                
                if ($meter->measures_electricity && isset($meterData['electricity_target_kwh'])) {
                    $electricityEuro = $this->conversionService->kwhToEuro($meterData['electricity_target_kwh']);
                }
                
                if ($meter->measures_gas && isset($meterData['gas_target_m3'])) {
                    $gasEuro = $this->conversionService->m3ToEuro($meterData['gas_target_m3']);
                }
                
                // Create or update the yearly budget for this meter
                $budget = EnergyBudget::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'smart_meter_id' => $meter->id,
                        'year' => $currentYear
                    ],
                    [
                        'electricity_target_kwh' => $meter->measures_electricity ? ($meterData['electricity_target_kwh'] ?? 0) : 0,
                        'electricity_target_euro' => $electricityEuro,
                        'gas_target_m3' => $meter->measures_gas ? ($meterData['gas_target_m3'] ?? 0) : 0,
                        'gas_target_euro' => $gasEuro,
                    ]
                );
                
                // Handle monthly budget data
                if (isset($meterData['monthly'])) {
                    // Delete existing monthly budgets for this meter and year
                    MonthlyEnergyBudget::where('user_id', $user->id)
                        ->where('energy_budget_id', $budget->id)
                        ->delete();
                    
                    // Create new monthly budgets
                    foreach ($meterData['monthly'] as $monthData) {
                        if (!isset($monthData['month'])) {
                            continue;
                        }
                        
                        MonthlyEnergyBudget::create([
                            'user_id' => $user->id,
                            'energy_budget_id' => $budget->id,
                            'month' => $monthData['month'],
                            'electricity_target_kwh' => $meter->measures_electricity ? ($monthData['electricity_target_kwh'] ?? 0) : 0,
                            'gas_target_m3' => $meter->measures_gas ? ($monthData['gas_target_m3'] ?? 0) : 0,
                            'is_default' => false,
                        ]);
                    }
                } else {
                    // Create default monthly budgets if no monthly data provided
                    $this->createDefaultMonthlyBudgets($budget, $meter);
                }
                
                $savedBudgets++;
            }
            
            DB::commit();
            
            return redirect()->route('budget.form')
                ->with('success', "Energiebudgetten voor {$savedBudgets} meter(s) succesvol opgeslagen!");
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('budget.form')
                ->with('error', 'Er is een fout opgetreden bij het opslaan van de budgetten: ' . $e->getMessage());
        }
    }

    /**
     * Get budget data for a specific meter
     */
    public function getBudgetForMeter(SmartMeter $meter, $year = null)
    {
        $year = $year ?? date('Y');
        
        $budget = EnergyBudget::where('user_id', Auth::id())
            ->where('smart_meter_id', $meter->id)
            ->where('year', $year)
            ->with(['monthlyBudgets' => function($query) {
                $query->orderBy('month');
            }])
            ->first();
        
        return $budget;
    }

    /**
     * Get all budgets for the current user
     */
    public function getAllBudgetsForUser($year = null)
    {
        $year = $year ?? date('Y');
        
        $budgets = EnergyBudget::where('user_id', Auth::id())
            ->where('year', $year)
            ->with(['smartMeter', 'monthlyBudgets' => function($query) {
                $query->orderBy('month');
            }])
            ->get();
        
        return $budgets;
    }

    /**
     * Create default monthly budgets for a meter
     */
    protected function createDefaultMonthlyBudgets(EnergyBudget $yearlyBudget, SmartMeter $meter)
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
            
            $electricityTarget = 0;
            $gasTarget = 0;
            
            if ($meter->measures_electricity && $yearlyBudget->electricity_target_kwh > 0) {
                $normalizedElectricityFactor = $monthlyElectricityFactors[$index] * 12 / $electricityFactorSum;
                $electricityTarget = round(($yearlyBudget->electricity_target_kwh / 12) * $normalizedElectricityFactor, 2);
            }
            
            if ($meter->measures_gas && $yearlyBudget->gas_target_m3 > 0) {
                $normalizedGasFactor = $monthlyGasFactors[$index] * 12 / $gasFactorSum;
                $gasTarget = round(($yearlyBudget->gas_target_m3 / 12) * $normalizedGasFactor, 2);
            }
            
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

    /**
     * Delete budget for a specific meter
     */
    public function deleteBudgetForMeter(SmartMeter $meter, $year = null)
    {
        $year = $year ?? date('Y');
        
        DB::beginTransaction();
        try {
            $budget = EnergyBudget::where('user_id', Auth::id())
                ->where('smart_meter_id', $meter->id)
                ->where('year', $year)
                ->first();
            
            if ($budget) {
                // Delete monthly budgets first
                MonthlyEnergyBudget::where('energy_budget_id', $budget->id)->delete();
                
                // Delete yearly budget
                $budget->delete();
            }
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * Legacy method for backwards compatibility
     * This will return the combined budgets of all meters for the user
     * @deprecated Use getAllBudgetsForUser() instead
     */
    public function calculate(Request $request)
    {
        // This method is kept for backwards compatibility
        // but should be updated to handle per-meter budgets
        
        return redirect()->route('budget.form')
            ->with('error', 'Deze functionaliteit is bijgewerkt. Stel budgetten per meter in.');
    }

    /**
     * Legacy method for backwards compatibility
     * @deprecated Use storePerMeter() instead
     */
    public function store(Request $request)
    {
        // Redirect to the new per-meter storage method
        return $this->storePerMeter($request);
    }
}