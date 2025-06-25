<?php

namespace App\Services;

use App\Models\User;
use App\Models\EnergyNotification;
use Carbon\Carbon;

class EnergyNotificationService
{
    /**
     * Check if a user should receive a notification based on their prediction data
     */
    public function checkAndCreateNotification(User $user, array $predictionData, string $period = 'month')
    {
        // Only create notification if user exceeds their budget
        if (!isset($predictionData['predicted_percentage']) || $predictionData['predicted_percentage'] <= 100) {
            return false;
        }

        // Check if user already has a recent notification for this type and period
        $recentNotification = EnergyNotification::where('user_id', $user->id)
            ->where('type', $predictionData['type'])
            ->where('status', '!=', 'dismissed')
            ->where('created_at', '>', now()->subDays($this->getNotificationCooldown($period)))
            ->first();

        if ($recentNotification) {
            return false;
        }

        // Create appropriate notification based on energy type
        if ($predictionData['type'] === 'electricity') {
            $this->createElectricityNotification($user, $predictionData, $period);
        } else {
            $this->createGasNotification($user, $predictionData, $period);
        }

        return true;
    }

    /**
     * Generate notifications for a user based on prediction data
     */
    public function generateNotificationsForUser(User $user, array $electricityPrediction = [], array $gasPrediction = [], string $period = 'month')
    {
        try {
            // Check electricity prediction
            if (!empty($electricityPrediction) && isset($electricityPrediction['predicted_percentage'])) {
                $this->checkAndCreateNotification($user, array_merge($electricityPrediction, ['type' => 'electricity']), $period);
            }

            // Check gas prediction  
            if (!empty($gasPrediction) && isset($gasPrediction['predicted_percentage'])) {
                $this->checkAndCreateNotification($user, array_merge($gasPrediction, ['type' => 'gas']), $period);
            }
        } catch (\Exception $e) {
            // Log error but don't break the dashboard
            \Log::error('Error generating notifications for user ' . $user->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Create an electricity usage notification
     */
    private function createElectricityNotification(User $user, array $data, string $period)
    {
        $severity = $this->getSeverityLevel($data['predicted_percentage'] - 100);
        $suggestions = $this->getCombinedSuggestions($user, 'electricity', $period);

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
        $suggestions = $this->getCombinedSuggestions($user, 'gas', $period);

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
     * Combineer standaard suggestions met gepersonaliseerde custom suggestions
     */
    private function getCombinedSuggestions(User $user, string $energyType, string $period): array
    {
        $suggestions = [];

        // Voeg custom suggestions toe (prioriteit)
        $customSuggestions = $this->getCustomSuggestions($user);
        $suggestions = array_merge($suggestions, $customSuggestions);

        // Voeg standaard suggestions toe
        if ($energyType === 'electricity') {
            $standardSuggestions = $this->getElectricitySavingSuggestions($period);
        } else {
            $standardSuggestions = $this->getGasSavingSuggestions($period);
        }

        $suggestions = array_merge($suggestions, $standardSuggestions);

        // Limiteer tot maximaal 5 suggestions voor leesbaarheid
        return array_slice($suggestions, 0, 5);
    }

    /**
     * Haal actieve custom suggestions op voor een gebruiker
     */
    private function getCustomSuggestions(User $user): array
    {
        $customSuggestions = $user->activeSuggestions()
            ->latest()
            ->limit(3) // Maximaal 3 custom suggestions
            ->get();

        return $customSuggestions->map(function ($suggestion) {
            return [
                'title' => $suggestion->title,
                'description' => $suggestion->suggestion,
                'saving' => 'Persoonlijke tip', // Aangepaste indicator
                'type' => 'custom' // Om onderscheid te maken
            ];
        })->toArray();
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
        return match ($period) {
            'day' => 'vandaag',
            'week' => 'deze week',
            'month' => 'deze maand',
            'year' => 'dit jaar',
            default => 'deze periode'
        };
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
                'type' => 'standard'
            ],
            [
                'title' => 'Apparaten volledig uitschakelen',
                'description' => 'Apparaten in stand-by modus verbruiken nog steeds stroom. Gebruik een stekkerdoos met schakelaar.',
                'saving' => 'tot 3 kWh per week',
                'type' => 'standard'
            ],
        ];

        // Add seasonal suggestions
        if ($season === 'winter') {
            $suggestions[] = [
                'title' => 'Verwarm efficiënt',
                'description' => 'Gebruik een elektrische deken in plaats van de hele slaapkamer te verwarmen.',
                'saving' => 'tot 10 kWh per week',
                'type' => 'standard'
            ];
        } elseif ($season === 'summer') {
            $suggestions[] = [
                'title' => 'Koel efficiënt',
                'description' => 'Gebruik een ventilator in plaats van airconditioning, of stel je airco 2 graden hoger in.',
                'saving' => 'tot 15 kWh per week',
                'type' => 'standard'
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
                'type' => 'standard'
            ],
            [
                'title' => 'Douche korter',
                'description' => 'Verkort je douchetijd met 2 minuten en bespaar warm water.',
                'saving' => 'tot 3 m³ gas per maand',
                'type' => 'standard'
            ],
        ];

        // Add seasonal suggestions
        if ($season === 'winter') {
            $suggestions[] = [
                'title' => 'Isolatie controleren',
                'description' => 'Controleer of ramen en deuren goed sluiten. Plaats tochtstrips waar nodig.',
                'saving' => 'tot 10% op je gasverbruik',
                'type' => 'standard'
            ];
        }

        return $suggestions;
    }

    /**
     * Get current season for seasonal suggestions
     */
    private function getCurrentSeason(): string
    {
        $month = Carbon::now()->month;

        if (in_array($month, [12, 1, 2])) {
            return 'winter';
        } elseif (in_array($month, [3, 4, 5])) {
            return 'spring';
        } elseif (in_array($month, [6, 7, 8])) {
            return 'summer';
        } else {
            return 'autumn';
        }
    }

    /**
     * Get notification cooldown period in days
     */
    private function getNotificationCooldown(string $period): int
    {
        return match ($period) {
            'day' => 1,
            'week' => 3,
            'month' => 7,
            'year' => 30,
            default => 7
        };
    }

    /**
     * Get expiration date for notifications
     */
    private function getExpirationDate(string $frequency): Carbon
    {
        return match ($frequency) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            default => now()->addWeek()
        };
    }
}