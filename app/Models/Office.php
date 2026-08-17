<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'user_id',
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
     * Get the user who owns this office.
     * 
     * @access public
     * @return BelongsTo
     */
    public function user() : BelongsTo {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user(s) who currently have this office set as active.
     * Normally zero or one - offices are personal, so this is not a
     * membership list.
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
