<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MenuItem model represents an item in a menu.
 * 
 * @extends Model
 * @author  Vladislav Riemer <dev@vladislav-riemer.de>
 */
class MenuItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * 
     * @var    array<int, string>
     * @access protected
     */
    protected $fillable = [
        'menu_id',
        'name',
        'description',
        'price',
        'sort_order'
    ];

    /**
     * The attributes that should be cast to native types.
     * 
     * @var    array<string, string>
     * @access protected
     */
    protected function casts() : array {
        return [
            'price' => 'decimal:2'
        ];
    }

    /**
     * Get the menu that owns the item.
     * 
     * @access public
     * @return BelongsTo
     */
    public function menu() : BelongsTo {
        return $this->belongsTo(Menu::class);
    }
}
