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
}
