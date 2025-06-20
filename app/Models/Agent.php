<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agent_code',
        'phone_number',
        'address',
        'status',
        'refusals_count',
        'refusals_month',
        'is_downgraded',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}