<?php

namespace App\Http\Middleware;

use App\Models\SmartMeter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckBudgetSetup
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only check for authenticated users
        if (!Auth::check()) {
            return $next($request);
        }

        // Skip check if already on budget setup pages
        if ($request->routeIs('budget.*')) {
            return $next($request);
        }

        $user = Auth::user();
        
        // Get user's smart meters
        $smartMeters = SmartMeter::forUser($user->id)->active()->get();
        
        // If user has no smart meters, they can't set budgets anyway
        if ($smartMeters->isEmpty()) {
            return $next($request);
        }
        
        // Check if any meter needs budget setup
        $needsSetup = false;
        foreach ($smartMeters as $meter) {
            if ($meter->needsBudgetSetup()) {
                $needsSetup = true;
                break;
            }
        }
        
        // If budget setup is needed and user is trying to access dashboard, redirect
        if ($needsSetup && ($request->routeIs('dashboard') || $request->routeIs('energy.*'))) {
            return redirect()->route('budget.form')
                ->with('warning', 'Stel eerst een budget in voor uw slimme meter(s) om het dashboard te kunnen gebruiken.');
        }
        
        return $next($request);
    }

    /**
     * Check if a user needs to set up budgets for their meters
     *
     * @param int|null $userId
     * @return bool
     */
    public static function userNeedsBudgetSetup(?int $userId = null): bool
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return false;
        }
        
        $smartMeters = SmartMeter::forUser($userId)->active()->get();
        
        if ($smartMeters->isEmpty()) {
            return false;
        }
        
        foreach ($smartMeters as $meter) {
            if ($meter->needsBudgetSetup()) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get meters that need budget setup for a user
     *
     * @param int|null $userId
     * @return \Illuminate\Support\Collection
     */
    public static function getMetersNeedingSetup(?int $userId = null): \Illuminate\Support\Collection
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return collect();
        }
        
        return SmartMeter::forUser($userId)
            ->active()
            ->get()
            ->filter(function ($meter) {
                return $meter->needsBudgetSetup();
            });
    }

    /**
     * Get budget setup status for dashboard
     *
     * @param int|null $userId
     * @return array
     */
    public static function getBudgetSetupStatus(?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return [
                'needs_setup' => false,
                'total_meters' => 0,
                'meters_with_budget' => 0,
                'meters_needing_setup' => 0,
                'meters_needing_setup_list' => collect(),
            ];
        }
        
        $smartMeters = SmartMeter::forUser($userId)->active()->get();
        $metersNeedingSetup = static::getMetersNeedingSetup($userId);
        
        return [
            'needs_setup' => $metersNeedingSetup->isNotEmpty(),
            'total_meters' => $smartMeters->count(),
            'meters_with_budget' => $smartMeters->count() - $metersNeedingSetup->count(),
            'meters_needing_setup' => $metersNeedingSetup->count(),
            'meters_needing_setup_list' => $metersNeedingSetup,
        ];
    }
}