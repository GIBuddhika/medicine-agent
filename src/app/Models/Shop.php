<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shop extends Model
{
    use SoftDeletes;

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function file()
    {
        return $this->belongsTo(File::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shopAdmins()
    {
        return $this->belongsToMany(User::class);
    }
    
    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
