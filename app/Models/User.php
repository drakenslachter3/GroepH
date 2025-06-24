<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * De attributen die massaal toegewezen mogen worden.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'description',
        'role',
        'additional_info',
        'active',
        'notification_frequency',
        'electricity_threshold',
        'gas_threshold',
        'include_suggestions',
        'include_comparison',
        'include_forecast',
    ];

    /**
     * De attributen die verborgen moeten zijn in arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * De attributen die gecast moeten worden.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    /**
     * Get de smart meters die aan deze gebruiker zijn gekoppeld.
     */
    public function smartMeters()
    {
        return $this->hasMany(SmartMeter::class, 'account_id');
    }

    /**
     * Get de notificaties voor deze gebruiker.
     */
    public function energyNotifications(): HasMany
    {
        return $this->hasMany(EnergyNotification::class);
    }

    /**
     * Get de ongelezen notificaties voor deze gebruiker.
     */
    public function unreadEnergyNotifications()
    {
        return $this->energyNotifications()
            ->where('status', 'unread')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at', 'desc');
    }

    /**
     * Bepaal of gebruiker de opgegeven rol heeft
     */
    public function hasRole($role)
    {
        if (is_array($role)) {
            return in_array($this->role, $role);
        }

        return $this->role === $role;
    }

    /**
     * Bepaal of een gebruiker de eigenaar is
     */
    public function isOwner()
    {
        return $this->role === 'owner';
    }

    /**
     * Bepaal of een gebruiker een beheerder is
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Bepaal of een gebruiker een standaard gebruiker is
     */
    public function isUser()
    {
        return $this->role === 'user';
    }

    /**
     * Geef een leesbare representatie van de rol
     */
    public function getRoleDisplayName()
    {
        switch ($this->role) {
            case 'owner':
                return 'Eigenaar';
            case 'admin':
                return 'Beheerder';
            case 'user':
            default:
                return 'Gebruiker';
        }
    }

    public function energyBudgets(): HasMany
    {
        return $this->hasMany(EnergyBudget::class);
    }

    /**
     * Get de notificatie frequentie van de gebruiker.
     */
    public function getNotificationFrequency(): string
    {
        return $this->notification_frequency ?? 'weekly';
    }

    public function suggestions()
    {
        return $this->hasMany(UserSuggestion::class);
    }

    public function activeSuggestions()
    {
        return $this->hasMany(UserSuggestion::class)->active();
    }
}