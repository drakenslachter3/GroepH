<?php

namespace App\Http\Controllers;

use App\Models\EnergyBudgetMargin;
use App\Models\EnergyDailyBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EnergyBudgetMarginController extends Controller
{
    /**
     * Store or update margin setting for energy budgets
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateMargin(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:gas,electricity',
            'margin' => 'required|numeric|min:5|max:30',
        ]);

        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::user()->id;
        $type = $validated['type'];
        $margin = $validated['margin'];
        
        // Store or update the margin setting
        $marginSetting = EnergyBudgetMargin::updateOrCreate(
            [
                'user_id' => $userId,
                'type' => $type
            ],
            [
                'margin_percentage' => $margin,
                'updated_at' => now()
            ]
        );

        // Return the updated setting
        return response()->json([
            'success' => true,
            'message' => ucfirst($type) . ' marge succesvol bijgewerkt',
            'data' => [
                'type' => $type,
                'margin' => $margin
            ]
        ]);
    }

    /**
     * Store or update daily budgets
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateBudgets(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:gas,electricity',
            'budgets' => 'required|array',
            'budgets.*' => 'required|numeric|min:0',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
            'startDay' => 'required|integer|min:1|max:31',
        ]);

        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::user()->id;
        $type = $validated['type'];
        $budgets = $validated['budgets'];
        $month = $validated['month'];
        $year = $validated['year'];
        $startDay = $validated['startDay'];

        // Validate the budgets against the margin constraint
        $marginSetting = EnergyBudgetMargin::where('user_id', $userId)
            ->where('type', $type)
            ->first();

        $margin = $marginSetting ? $marginSetting->margin_percentage : 15; // Default margin
        
        // Verify budgets don't exceed margin constraint
        for ($i = 1; $i < count($budgets); $i++) {
            $prevBudget = $budgets[$i-1];
            $currentBudget = $budgets[$i];
            $maxChange = ceil($prevBudget * ($margin / 100));
            
            if (abs($currentBudget - $prevBudget) > $maxChange) {
                return response()->json([
                    'success' => false,
                    'message' => 'Een of meer budgetten overschrijden de ' . $margin . '% marge.',
                    'error' => 'margin_exceeded'
                ], 422);
            }
        }

        // Store the daily budgets
        $date = Carbon::createFromDate($year, $month, $startDay);
        
        foreach ($budgets as $index => $value) {
            $currentDate = $date->copy()->addDays($index);
            
            EnergyDailyBudget::updateOrCreate(
                [
                    'user_id' => $userId,
                    'type' => $type,
                    'date' => $currentDate->format('Y-m-d')
                ],
                [
                    'budget_value' => $value,
                    'updated_at' => now()
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => ucfirst($type) . ' budgetten succesvol bijgewerkt',
            'data' => [
                'type' => $type,
                'count' => count($budgets)
            ]
        ]);
    }

    /**
     * Get margin settings for current user
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMarginSettings()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::user()->id;
        
        $gasMargin = EnergyBudgetMargin::where('user_id', $userId)
            ->where('type', 'gas')
            ->first();
            
        $electricityMargin = EnergyBudgetMargin::where('user_id', $userId)
            ->where('type', 'electricity')
            ->first();
        
        return response()->json([
            'success' => true,
            'data' => [
                'gas' => $gasMargin ? $gasMargin->margin_percentage : 15,
                'electricity' => $electricityMargin ? $electricityMargin->margin_percentage : 15
            ]
        ]);
    }

    /**
     * Get daily budgets for current month
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDailyBudgets(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
        ]);

        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = Auth::user()->id;
        $month = $validated['month'];
        $year = $validated['year'];
        
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        $gasBudgets = EnergyDailyBudget::where('user_id', $userId)
            ->where('type', 'gas')
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('date')
            ->get()
            ->pluck('budget_value', 'date');
            
        $electricityBudgets = EnergyDailyBudget::where('user_id', $userId)
            ->where('type', 'electricity')
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('date')
            ->get()
            ->pluck('budget_value', 'date');
        
        // Convert to arrays indexed by day number
        $daysInMonth = $endDate->day;
        $gasArray = [];
        $electricityArray = [];
        
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
            $gasArray[$day] = $gasBudgets->get($date, null);
            $electricityArray[$day] = $electricityBudgets->get($date, null);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'gas' => $gasArray,
                'electricity' => $electricityArray,
                'month' => $month,
                'year' => $year,
                'days_in_month' => $daysInMonth
            ]
        ]);
    }
}