<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property User|null $user
 */

class LoginSession extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'user_id', 'ip_address', 'user_agent', 'status', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
