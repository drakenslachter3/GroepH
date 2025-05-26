<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\EnergyNotification;
use Carbon\Carbon;

class TestNotificationController extends Controller
{
    public function generateTestNotification(Request $request)
    {
        $user = Auth::user();
        $period = $request->query('period', 'month');
        $type = $request->query('type', 'both');

        // Testdata
        $electricityData = [
            'predicted_percentage' => 115,
            'predicted_total' => 4000,
            'target' => 3500,
        ];
        
        $gasData = [
            'predicted_percentage' => 110,
            'predicted_total' => 1320,
            'target' => 1200,
        ];

        // Geen service gebruiken, direct de notificatie aanmaken
        if ($type === 'electricity' || $type === 'both') {
            $this->createTestElectricityNotification($user, $electricityData, $period);
        }
        
        if ($type === 'gas' || $type === 'both') {
            $this->createTestGasNotification($user, $gasData, $period);
        }

        return redirect()->route('notifications.index')
            ->with('status', "Testnotificatie(s) gegenereerd voor periode: {$period}");
    }

    private function createTestElectricityNotification($user, $data, $period)
    {
        $severity = $this->getSeverityLevel($data['predicted_percentage'] - 100);
        
        EnergyNotification::create([
            'user_id' => $user->id,
            'type' => 'electricity',
            'severity' => $severity,
            'threshold_percentage' => $data['predicted_percentage'] - 100,
            'target_reduction' => $data['predicted_total'] - $data['target'],
            'message' => $this->generateMessage($data, $period, 'elektriciteitsbudget', 'kWh'),
            'suggestions' => $this->getElectricitySuggestions(),
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);
    }
    
    private function createTestGasNotification($user, $data, $period)
    {
        $severity = $this->getSeverityLevel($data['predicted_percentage'] - 100);
        
        EnergyNotification::create([
            'user_id' => $user->id,
            'type' => 'gas',
            'severity' => $severity,
            'threshold_percentage' => $data['predicted_percentage'] - 100,
            'target_reduction' => $data['predicted_total'] - $data['target'],
            'message' => $this->generateMessage($data, $period, 'gasbudget', 'm³'),
            'suggestions' => $this->getGasSuggestions(),
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);
    }
    
    private function generateMessage($data, $period, $budgetType, $unit)
    {
        $percentage = round($data['predicted_percentage'] - 100);
        $reduction = round($data['predicted_total'] - $data['target'], 1);
        $periodLabel = $this->getPeriodLabel($period);
        
        return "Volgens onze voorspelling zal je dit {$periodLabel} {$percentage}% over je {$budgetType} gaan. Je kunt dit voorkomen door je verbruik met {$reduction} {$unit} te verminderen.";
    }
    
    private function getPeriodLabel($period)
    {
        switch ($period) {
            case 'day': return 'deze dag';
            case 'month': return 'deze maand';
            case 'year': return 'dit jaar';
            default: return $period;
        }
    }
    
    private function getSeverityLevel($percentage)
    {
        if ($percentage >= 20) return 'critical';
        if ($percentage >= 10) return 'warning';
        return 'info';
    }
    
    private function getElectricitySuggestions()
    {
        return [
            [
                'title' => 'Verlichting efficiënt gebruiken',
                'description' => 'Vervang gloeilampen door LED en schakel lichten uit in ruimtes waar je niet bent.',
                'saving' => 'tot 5 kWh per week',
            ],
            [
                'title' => 'Apparaten volledig uitschakelen',
                'description' => 'Apparaten in stand-by modus verbruiken nog steeds stroom. Gebruik een stekkerdoos met schakelaar.',
                'saving' => 'tot 3 kWh per week',
            ],
        ];
    }
    
    private function getGasSuggestions()
    {
        return [
            [
                'title' => 'Verwarm slim',
                'description' => 'Zet de thermostaat 1 graad lager en draag een extra laag kleding.',
                'saving' => 'tot 5% op je gasverbruik',
            ],
            [
                'title' => 'Douche korter',
                'description' => 'Verkort je douchetijd met 2 minuten en bespaar warm water.',
                'saving' => 'tot 3 m³ gas per maand',
            ],
        ];
    }
}