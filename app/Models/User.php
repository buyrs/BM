<?php

namespace App\Models;

use App\Traits\HasEncryptedAttributes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles, HasEncryptedAttributes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        ];
    }

    /**
     * The attributes that should be encrypted
     */
    protected $encrypted = [];

    /**
     * The attributes that should be searchable while encrypted
     */
    protected $searchableEncrypted = [
        'email'
    ];

    /**
     * Get the agent associated with the user.
     */
    public function agent()
    {
        return $this->hasOne(Agent::class);
    }

    /**
     * Get the missions assigned to this user (as a checker).
     */
    public function assignedMissions()
    {
        return $this->hasMany(Mission::class, 'agent_id');
    }

    /**
     * Get the bail mobilités managed by this user (as ops).
     */
    public function managedBailMobilites()
    {
        return $this->hasMany(BailMobilite::class, 'ops_user_id');
    }

    /**
     * Get the missions assigned by this user (as ops).
     */
    public function assignedMissionsByOps()
    {
        return $this->hasMany(Mission::class, 'ops_assigned_by');
    }
}
