<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Account extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * De attributen die massaal toegewezen mogen worden.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'postal_code',
        'city',
        'role',
        'active'
    ];

    /**
     * De attributen die verborgen moeten zijn in arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * De attributen die gecast moeten worden.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'active' => 'boolean',
    ];

    /**
     * Get de smart meter die aan dit account is gekoppeld.
     */
    public function smartMeter()
    {
        return $this->hasOne(SmartMeter::class);
    }

    /**
     * Bepaal of account de opgegeven rol heeft
     */
    public function hasRole($role)
    {
        if (is_array($role)) {
            return in_array($this->role, $role);
        }
        
        return $this->role === $role;
    }
    
    /**
     * Bepaal of een account de eigenaar is
     */
    public function isOwner()
    {
        return $this->role === 'owner';
    }
    
    /**
     * Bepaal of een account een beheerder is
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
    
    /**
     * Bepaal of een account een standaard gebruiker is
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
}