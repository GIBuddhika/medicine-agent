<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    public function files()
    {
        return $this->belongsToMany(File::class);
    }

    public function sellableItem()
    {
        return $this->hasOne(SellableItem::class);
    }

    public function rentableItem()
    {
        return $this->hasOne(RentableItem::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
