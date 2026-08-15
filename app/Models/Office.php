<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Office model represents an office location.
 * 
 * @extends Model
 * @author  Vladislav Riemer <dev@vladislav-riemer.de>
 */
class Office extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * 
     * @var    array<int, string>
     * @access protected
     */
    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'max_distance_meters',
        'max_walking_minutes',
        'distance_unit',
        'geocoded_at'
    ];

    /**
     * The attributes that should be cast to native types.
     * 
     * @var    array<string, string>
     * @access protected
     */
    protected function casts() : array {
        return [
            'latitude'    => 'decimal:7',
            'longitude'   => 'decimal:7',
            'geocoded_at' => 'datetime'
        ];
    }

    /**
     * Get the users associated with the office.
     * 
     * @access public
     * @return HasMany
     */
    public function users() : HasMany {
        return $this->hasMany(User::class);
    }

    /**
     * Get the restaurants associated with the office.
     * 
     * @access public
     * @return HasMany
     */
    public function restaurants() : HasMany {
        return $this->hasMany(Restaurant::class);
    }
}
