<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'name',
        'source',
        'external_id',
        'address',
        'latitude',
        'longitude',
        'category',
        'walking_distance_meters',
        'walking_duration_seconds',
        'distance_calculated_at',
        'is_active',
        'menu_source_type',
        'menu_source_config',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'distance_calculated_at' => 'datetime',
            'is_active' => 'boolean',
            'menu_source_config' => 'array',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
    }
}
