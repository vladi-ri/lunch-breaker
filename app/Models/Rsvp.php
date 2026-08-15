<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rsvp model represents a user's RSVP for a restaurant on a specific date.
 * 
 * @extends Model
 * @author  Vladislav Riemer <dev@vladislav-riemer.de>
 */
class Rsvp extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'restaurant_id',
        'date',
        'status'
    ];

    /**
     * The attributes that should be cast to native types.
     * 
     * @var    array<string, string>
     * @access protected
     */
    protected function casts() : array {
        return [
            'date' => 'date'
        ];
    }

    /**
     * Get the user that owns the RSVP.
     * 
     * @access public
     * @return BelongsTo
     */
    public function user() : BelongsTo {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the restaurant that owns the RSVP.
     * 
     * @access public
     * @return BelongsTo
     */
    public function restaurant() : BelongsTo {
        return $this->belongsTo(Restaurant::class);
    }
}
