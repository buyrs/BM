<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class SharedLink extends Model
{
    protected $fillable = ['checklist_id', 'token', 'expires_at'];

    protected $dates = ['expires_at'];

    public static function generateForChecklist($checklist_id, $expiry_minutes = 60)
    {
        $token = Str::random(40);
        return self::create([
            'checklist_id' => $checklist_id,
            'token' => $token,
            'expires_at' => now()->addMinutes($expiry_minutes),
        ]);
    }

    public function getUrlAttribute()
    {
        return URL::temporarySignedRoute('shared.checklist.pdf', $this->expires_at, ['token' => $this->token]);
    }

    public function checklist()
    {
        return $this->belongsTo(Checklist::class);
    }
}