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
        // Only fetch notifications if user is authenticated
        if (Auth::check()) {
            $this->unreadNotifications = EnergyNotification::getUnreadForUser(Auth::user());
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