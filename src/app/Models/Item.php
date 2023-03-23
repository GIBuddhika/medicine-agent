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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function personalListing()
    {
        return $this->belongsTo(PersonalListing::class);
    }

    public function mainImage()
    {
        return $this->files()
            ->where('id', $this->image_id)
            ->first();
    }
}
