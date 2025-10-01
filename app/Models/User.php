<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'two_factor_enabled',
        'preferences',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'array',
            'last_login_at' => 'datetime',
            'preferences' => 'array',
        ];
    }

    /**
     * Check if the user has two-factor authentication enabled.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled && !is_null($this->two_factor_secret);
    }

    /**
     * Get the user's two-factor authentication recovery codes.
     */
    public function getRecoveryCodes(): array
    {
        return $this->two_factor_recovery_codes ?? [];
    }

    /**
     * Replace the given recovery code with a new one in the recovery codes array.
     */
    public function replaceRecoveryCode(string $code): void
    {
        $this->two_factor_recovery_codes = collect($this->two_factor_recovery_codes)
            ->reject(fn ($recoveryCode) => hash_equals($recoveryCode, $code))
            ->values()
            ->all();

        $this->save();
    }

    /**
     * Generate new recovery codes for the user.
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtolower(bin2hex(random_bytes(5)));
        }

        $this->two_factor_recovery_codes = $codes;
        $this->save();

        return $codes;
    }

    /**
     * Get the audit logs for this user
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get the notifications for this user
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get unread notifications for this user
     */
    public function unreadNotifications(): HasMany
    {
        return $this->notifications()->whereNull('read_at');
    }

    /**
     * Get notifications requiring action for this user
     */
    public function actionRequiredNotifications(): HasMany
    {
        return $this->notifications()
            ->where('requires_action', true)
            ->whereNull('action_taken_at');
    }

    /**
     * Get missions assigned to this user as checker
     */
    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class, 'checker_id');
    }

    /**
     * Get missions assigned to this user as ops
     */
    public function opsMissions(): HasMany
    {
        return $this->hasMany(Mission::class, 'ops_id');
    }

    /**
     * Get missions created by this user as admin
     */
    public function adminMissions(): HasMany
    {
        return $this->hasMany(Mission::class, 'admin_id');
    }
}
