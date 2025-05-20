<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnergyNotification extends Model
{
    use HasFactory;

    /**
     * De attributen die massaal toegewezen mogen worden.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'severity',
        'threshold_percentage',
        'target_reduction',
        'message',
        'suggestions',
        'status',
        'read_at',
        'expires_at',
    ];

    /**
     * De attributen die gecast moeten worden.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'threshold_percentage' => 'float',
        'target_reduction' => 'float',
        'suggestions' => 'array',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get de gebruiker waartoe deze notificatie behoort.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bepaal of de notificatie ongelezen is.
     */
    public function isUnread(): bool
    {
        return $this->status === 'unread';
    }

    /**
     * Bepaal of de notificatie verlopen is.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at < now();
    }

    /**
     * Markeert de notificatie als gelezen.
     */
    public function markAsRead(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    /**
     * Markeert de notificatie als verwijderd.
     */
    public function dismiss(): void
    {
        $this->update([
            'status' => 'dismissed',
        ]);
    }

    /**
     * Geeft alle ongelezen notificaties voor een gebruiker.
     */
    public static function getUnreadForUser(User $user)
    {
        return self::where('user_id', $user->id)
            ->where('status', 'unread')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Geeft een korte beschrijving van het type notificatie.
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'electricity' => 'Elektriciteit',
            'gas' => 'Gas',
            'both' => 'Elektriciteit & Gas',
            default => 'Energie',
        };
    }

    /**
     * Geeft een CSS kleur klasse op basis van de severity.
     */
    public function getSeverityColorClass(): string
    {
        return match($this->severity) {
            'info' => 'blue',
            'warning' => 'yellow',
            'critical' => 'red',
            default => 'gray',
        };
    }
}