<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mission extends Model
{
    use HasFactory;
{
    protected $fillable = [
        'title',
        'description',
        'property_address',
        'checkin_date',
        'checkout_date',
        'status',
        'admin_id',
        'ops_id',
        'checker_id',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function ops(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ops_id');
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checker_id');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class);
    }
}
