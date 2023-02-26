<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserMeta extends Model
{
    use SoftDeletes;

    protected $table = 'user_meta';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
