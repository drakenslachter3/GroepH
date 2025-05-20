<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\EnergyNotification;
use Illuminate\Support\Facades\Auth;

class EnergyNotificationInbox extends Component
{
    /**
     * The pending notification requests
     */
    public $unreadNotifications;

    /**
     * Count of unread notifications
     */
    public $unreadCount;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        if (Auth::check()) {
            $this->unreadNotifications = EnergyNotification::where('user_id', Auth::id())
                ->where('status', 'unread')  // Zorg dat alleen 'unread' status wordt geteld!
                ->where('expires_at', '>', now())
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            $this->unreadCount = $this->unreadNotifications->count();
        } else {
            $this->unreadNotifications = collect();
            $this->unreadCount = 0;
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.energy-notification-inbox');
    }
}