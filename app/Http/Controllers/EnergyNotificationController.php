<?php

namespace App\Http\Controllers;

use App\Models\EnergyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnergyNotificationController extends Controller
{
    /**
     * Display the user's energy notifications.
     */
    public function index()
    {
        $user = Auth::user();
        $notifications = EnergyNotification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('energy.notifications.index', compact('notifications'));
    }
    
    /**
     * Mark a notification as read.
     */
    public function markAsRead(EnergyNotification $notification)
    {
        // Check if the notification belongs to the user
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $notification->markAsRead();
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Dismiss a notification.
     */
    public function dismiss(EnergyNotification $notification)
    {
        // Check if the notification belongs to the user
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $notification->dismiss();
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Display the notification settings form.
     */
    public function settings()
    {
        $user = Auth::user();
        
        return view('energy.notifications.settings', [
            'user' => $user,
            'frequency' => $user->getNotificationFrequency(),
        ]);
    }
    
    /**
     * Update the user's notification settings.
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'notification_frequency' => 'required|in:daily,weekly,monthly,never',
            'electricity_threshold' => 'required|integer|min:1|max:50',
            'gas_threshold' => 'required|integer|min:1|max:50',
        ]);
        
        $user = Auth::user();
        $user->notification_frequency = $request->notification_frequency;
        $user->electricity_threshold = $request->electricity_threshold;
        $user->gas_threshold = $request->gas_threshold;
        $user->include_suggestions = $request->has('include_suggestions') ? 1 : 0;
        $user->include_comparison = $request->has('include_comparison') ? 1 : 0;
        $user->include_forecast = $request->has('include_forecast') ? 1 : 0;
        $user->save();
        
        return redirect()->route('notifications.settings')
            ->with('status', 'Notificatie-instellingen succesvol bijgewerkt!');
    }
}