<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company_name',
        'address',
        'user_id',       // Associated user account
        'status',        // active, inactive, suspended
        'settings',      // Client preferences
        'contract_start',
        'contract_end',
    ];

    protected $casts = [
        'settings' => 'array',
        'contract_start' => 'date',
        'contract_end' => 'date',
    ];

    /**
     * Get the user account for this client.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get properties owned by this client.
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    /**
     * Get all missions for client's properties.
     */
    public function missions()
    {
        return Mission::whereIn('property_id', $this->properties()->pluck('id'));
    }

    /**
     * Scope: active clients.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if client can access a specific property.
     */
    public function canAccessProperty(Property $property): bool
    {
        return $this->properties()->where('id', $property->id)->exists();
    }

    /**
     * Check if client can access a specific mission.
     */
    public function canAccessMission(Mission $mission): bool
    {
        return $this->properties()
            ->where('id', $mission->property_id)
            ->exists();
    }

    /**
     * Get property IDs for this client (for query scoping).
     */
    public function getPropertyIds(): array
    {
        return $this->properties()->pluck('id')->toArray();
    }

    /**
     * Get dashboard stats for this client.
     */
    public function getDashboardStats(): array
    {
        $propertyIds = $this->getPropertyIds();

        return [
            'total_properties' => count($propertyIds),
            'active_missions' => Mission::whereIn('property_id', $propertyIds)
                ->whereIn('status', ['pending', 'in_progress'])
                ->count(),
            'completed_missions' => Mission::whereIn('property_id', $propertyIds)
                ->where('status', 'completed')
                ->count(),
            'recent_inspections' => Mission::whereIn('property_id', $propertyIds)
                ->where('status', 'completed')
                ->where('completed_at', '>=', now()->subDays(30))
                ->count(),
            'pending_issues' => Mission::whereIn('property_id', $propertyIds)
                ->where('has_issues', true)
                ->where('issues_resolved', false)
                ->count(),
        ];
    }
}
