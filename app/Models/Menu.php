<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Menu model represents a menu for a restaurant on a specific date.
 * 
 * @extends Model
 * @author  Vladislav Riemer <dev@vladislav-riemer.de>
 */
class Menu extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * 
     * @var    array<int, string>
     * @access protected
     */
    protected $fillable = [
        'restaurant_id',
        'date',
        'source_type',
        'fetched_at',
        'raw_text'
    ];

    /**
     * The attributes that should be cast to native types.
     * 
     * @var    array<string, string>
     * @access protected
     */
    protected function casts() : array {
        return [
            'date'       => 'date',
            'fetched_at' => 'datetime'
        ];
    }

    /**
     * Get the restaurant that owns the menu.
     * 
     * @access public
     * @return BelongsTo
     */
    public function restaurant() : BelongsTo {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get the items associated with the menu.
     * 
     * @access public
     * @return HasMany
     */
    public function items() : HasMany {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }
}
