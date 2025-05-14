<?php

namespace App\Services;

use App\Models\EnergyNotification;
use App\Models\User;
use App\Services\EnergyPredictionService;
use Carbon\Carbon;

class EnergyNotificationService
{
    protected $predictionService;
    
    public function __construct(EnergyPredictionService $predictionService)
    {
        $this->predictionService = $predictionService;
    }
    
    /**
     * Generate notifications for a user based on their energy usage predictions
     */
    public function generateNotificationsForUser(User $user, array $electricityData, array $gasData, string $period)
    {
        // Don't generate notifications if the user has opted out
        if ($user->getNotificationFrequency() === 'never') {
            return;
        }
        
        // Check the last time we generated notifications
        $lastNotification = EnergyNotification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();
            
        // Check if we should generate based on user's preferred frequency
        if ($lastNotification) {
            $shouldGenerate = $this->shouldGenerateNotification(
                $lastNotification->created_at,
                $user->getNotificationFrequency()
            );
            
            if (!$shouldGenerate) {
                return;
            }
        }
        
        // Check if the user is on track for their electricity usage
        $electricityThreshold = $user->electricity_threshold ?? 10;
        if ($electricityData && $this->isOverThreshold($electricityData, $electricityThreshold)) {
            $this->createElectricityNotification($user, $electricityData, $period);
        }
        
        // Check if the user is on track for their gas usage
        $gasThreshold = $user->gas_threshold ?? 10;
        if ($gasData && $this->isOverThreshold($gasData, $gasThreshold)) {
            $this->createGasNotification($user, $gasData, $period);
        }
    }
    
    /**
     * Determine if we should generate a new notification based on frequency
     */
    private function shouldGenerateNotification(Carbon $lastCreated, string $frequency): bool
    {
        switch ($frequency) {
            case 'daily':
                return $lastCreated->diffInDays(now()) >= 1;
            case 'weekly':
                return $lastCreated->diffInWeeks(now()) >= 1;
            case 'monthly':
                return $lastCreated->diffInMonths(now()) >= 1;
            default:
                return false;
        }
    }
    
    /**
     * Check if the predicted usage is over the threshold percentage
     */
    private function isOverThreshold(array $data, int $thresholdPercentage): bool
    {
        // If predicted usage is over the threshold percentage (e.g., 110% of target)
        return isset($data['predicted_percentage']) && 
               ($data['predicted_percentage'] - 100) >= $thresholdPercentage;
    }
    
    /**
     * Create an electricity usage notification
     */
    private function createElectricityNotification(User $user, array $data, string $period)
    {
        $severity = $this->getSeverityLevel($data['predicted_percentage'] - 100);
        $suggestions = $this->getElectricitySavingSuggestions($period);
        
        EnergyNotification::create([
            'user_id' => $user->id,
            'type' => 'electricity',
            'severity' => $severity,
            'threshold_percentage' => $data['predicted_percentage'] - 100,
            'target_reduction' => $this->calculateTargetReduction($data),
            'message' => $this->generateElectricityMessage($data, $period),
            'suggestions' => $suggestions,
            'status' => 'unread',
            'expires_at' => $this->getExpirationDate($user->getNotificationFrequency()),
        ]);
    }
    
    /**
     * Create a gas usage notification
     */
    private function createGasNotification(User $user, array $data, string $period)
    {
        $severity = $this->getSeverityLevel($data['predicted_percentage'] - 100);
        $suggestions = $this->getGasSavingSuggestions($period);
        
        EnergyNotification::create([
            'user_id' => $user->id,
            'type' => 'gas',
            'severity' => $severity,
            'threshold_percentage' => $data['predicted_percentage'] - 100,
            'target_reduction' => $this->calculateTargetReduction($data),
            'message' => $this->generateGasMessage($data, $period),
            'suggestions' => $suggestions,
            'status' => 'unread',
            'expires_at' => $this->getExpirationDate($user->getNotificationFrequency()),
        ]);
    }
    
    /**
     * Get the severity level based on how much the target is exceeded
     */
    private function getSeverityLevel(float $percentage): string
    {
        if ($percentage >= 20) {
            return 'critical';
        }
        
        if ($percentage >= 10) {
            return 'warning';
        }
        
        return 'info';
    }
    
    /**
     * Calculate how much the user needs to reduce to meet their target
     */
    private function calculateTargetReduction(array $data): float
    {
        // Calculate the amount that exceeds the target
        return isset($data['predicted_total']) && isset($data['target']) 
            ? $data['predicted_total'] - $data['target'] 
            : 0;
    }
    
    /**
     * Generate message for electricity notification
     */
    private function generateElectricityMessage(array $data, string $period): string
    {
        $percentage = round($data['predicted_percentage'] - 100);
        $reduction = round($this->calculateTargetReduction($data), 1);
        $periodLabel = $this->getPeriodLabel($period);
        
        return "Volgens onze voorspelling zal je dit {$periodLabel} {$percentage}% over je elektriciteitsbudget gaan. Je kunt dit voorkomen door je verbruik met {$reduction} kWh te verminderen.";
    }
    
    /**
     * Generate message for gas notification
     */
    private function generateGasMessage(array $data, string $period): string
    {
        $percentage = round($data['predicted_percentage'] - 100);
        $reduction = round($this->calculateTargetReduction($data), 1);
        $periodLabel = $this->getPeriodLabel($period);
        
        return "Volgens onze voorspelling zal je dit {$periodLabel} {$percentage}% over je gasbudget gaan. Je kunt dit voorkomen door je verbruik met {$reduction} m³ te verminderen.";
    }
    
    /**
     * Convert period code to readable label
     */
    private function getPeriodLabel(string $period): string
    {
        switch ($period) {
            case 'day':
                return 'deze dag';
            case 'month':
                return 'deze maand';
            case 'year':
                return 'dit jaar';
            default:
                return $period;
        }
    }
    
    /**
     * Get suggestions for reducing electricity
     */
    private function getElectricitySavingSuggestions(string $period): array
    {
        $season = $this->getCurrentSeason();
        
        $suggestions = [
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
        
        // Add seasonal suggestions
        if ($season === 'winter') {
            $suggestions[] = [
                'title' => 'Verwarm efficiënt',
                'description' => 'Gebruik een elektrische deken in plaats van de hele slaapkamer te verwarmen.',
                'saving' => 'tot 10 kWh per week',
            ];
        } elseif ($season === 'summer') {
            $suggestions[] = [
                'title' => 'Koel efficiënt',
                'description' => 'Gebruik een ventilator in plaats van airconditioning, of stel je airco 2 graden hoger in.',
                'saving' => 'tot 15 kWh per week',
            ];
        }
        
        return $suggestions;
    }
    
    /**
     * Get suggestions for reducing gas
     */
    private function getGasSavingSuggestions(string $period): array
    {
        $season = $this->getCurrentSeason();
        
        $suggestions = [
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
        
        // Add seasonal suggestions
        if ($season === 'winter') {
            $suggestions[] = [
                'title' => 'Voorkom warmteverlies',
                'description' => 'Plaats radiatorfolie achter je radiatoren en gebruik tochtstrips.',
                'saving' => 'tot 5% op je gasverbruik',
            ];
        } elseif ($season === 'summer') {
            $suggestions[] = [
                'title' => 'Zet de CV-ketel uit',
                'description' => 'In de zomer heb je vaak geen verwarming nodig. Zet je CV-ketel in zomerstand.',
                'saving' => 'tot 7% op je gasverbruik',
            ];
        }
        
        return $suggestions;
    }
    
    /**
     * Get current season
     */
    private function getCurrentSeason(): string
    {
        $month = date('n');
        
        if ($month >= 3 && $month <= 5) {
            return 'spring';
        } elseif ($month >= 6 && $month <= 8) {
            return 'summer';
        } elseif ($month >= 9 && $month <= 11) {
            return 'autumn';
        } else {
            return 'winter';
        }
    }
    
    /**
     * Get expiration date based on frequency
     */
    private function getExpirationDate(string $frequency): Carbon
    {
        switch ($frequency) {
            case 'daily':
                return now()->addDays(2);
            case 'weekly':
                return now()->addWeeks(2);
            case 'monthly':
                return now()->addMonths(2);
            default:
                return now()->addWeeks(1);
        }
    }
}