<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalListing extends Model
{
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
