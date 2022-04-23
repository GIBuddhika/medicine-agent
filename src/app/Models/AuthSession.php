<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthSession extends Model
{
    protected $dates = [
        'expire_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
