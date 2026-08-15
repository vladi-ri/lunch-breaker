<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User model represents a user of the application.
 * 
 * @extends Authenticatable
 * @author  Vladislav Riemer <dev@vladislav-riemer.de>
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory,
        Notifiable;

    /**
     * The attributes that are mass assignable.
     * 
     * @var    list<string>
     * @access protected
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'office_id',
        'is_admin'
    ];

    /**
     * The attributes that should be hidden for serialization.
     * 
     * @var    list<string>
     * @access protected
     */
    protected $hidden   = [
        'password',
        'remember_token'
    ];

    /**
     * Get the attributes that should be cast.
     * 
     * @access protected
     * @return array<string, string>
     */
    protected function casts() : array {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean'
        ];
    }

    /**
     * Get the office that the user belongs to.
     * 
     * @access public
     * @return BelongsTo
     */
    public function office() : BelongsTo {
        return $this->belongsTo(Office::class);
    }
}
