<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\PasswordResetRequest;
use Illuminate\Support\Facades\Auth;

class AdminNotificationInbox extends Component
{
    /**
     * The pending notification requests
     */
    public $pendingRequests;
    
    /**
     * Count of pending notifications
     */
    public $pendingCount;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Only fetch requests if user is admin
        if (Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isOwner())) {
            $this->pendingRequests = PasswordResetRequest::with('user')
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->orderBy('created_at', 'desc')
                ->take(5) // Limit to 5 most recent for the dropdown
                ->get();
                
            $this->pendingCount = $this->pendingRequests->count();
        } else {
            $this->pendingRequests = collect();
            $this->pendingCount = 0;
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.admin-notification-inbox');
    }
}