<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checklist extends Model
{
    protected $fillable = [
        'mission_id',
        'general_info',
        'rooms',
        'utilities',
        'tenant_signature',
        'agent_signature',
        'status'
    ];

    protected $casts = [
        'general_info' => 'array',
        'rooms' => 'array',
        'utilities' => 'array'
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class);
    }

    public function getDefaultStructure(): array
    {
        return [
            'general_info' => [
                'heating' => [
                    'type' => null,
                    'condition' => null,
                    'comment' => null
                ],
                'hot_water' => [
                    'type' => null,
                    'condition' => null,
                    'comment' => null
                ],
                'keys' => [
                    'count' => 0,
                    'condition' => null,
                    'comment' => null
                ]
            ],
            'rooms' => [
                'entrance' => [
                    'walls' => null,
                    'floor' => null,
                    'ceiling' => null,
                    'door' => null,
                    'windows' => null,
                    'electrical' => null
                ],
                'living_room' => [
                    'walls' => null,
                    'floor' => null,
                    'ceiling' => null,
                    'windows' => null,
                    'electrical' => null,
                    'heating' => null
                ],
                'kitchen' => [
                    'walls' => null,
                    'floor' => null,
                    'ceiling' => null,
                    'windows' => null,
                    'electrical' => null,
                    'plumbing' => null,
                    'appliances' => null
                ]
            ],
            'utilities' => [
                'electricity_meter' => [
                    'number' => null,
                    'reading' => null
                ],
                'gas_meter' => [
                    'number' => null,
                    'reading' => null
                ],
                'water_meter' => [
                    'number' => null,
                    'reading' => null
                ]
            ]
        ];
    }
}