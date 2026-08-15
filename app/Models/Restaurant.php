<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Restaurant model represents a restaurant associated with an office.
 * 
 * @extends Model
 * @author  Vladislav Riemer <dev@vladislav-riemer.de>
 */
class Restaurant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * 
     * @var    array<int, string>
     * @access protected
     */
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
        'menu_source_config'
    ];

    /**
     * The attributes that should be cast to native types.
     * 
     * @var    array<string, string>
     * @access protected
     */
    protected function casts() : array {
        return [
            'latitude'               => 'decimal:7',
            'longitude'              => 'decimal:7',
            'distance_calculated_at' => 'datetime',
            'is_active'              => 'boolean',
            'menu_source_config'     => 'array'
        ];
    }

    /**
     * Get the office that owns the restaurant.
     * 
     * @access public
     * @return BelongsTo
     */
    public function office() : BelongsTo {
        return $this->belongsTo(Office::class);
    }

    /**
     * Get the menus associated with the restaurant.
     * 
     * @access public
     * @return HasMany
     */
    public function menus() : HasMany {
        return $this->hasMany(Menu::class);
    }

    /**
     * Get the RSVPs associated with the restaurant.
     * 
     * @access public
     * @return HasMany
     */
    public function rsvps() : HasMany {
        return $this->hasMany(Rsvp::class);
    }
}
