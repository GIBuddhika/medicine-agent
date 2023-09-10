<?php

namespace App\Models;

use App\Constants\ProductCategoryConstants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    public function files()
    {
        return $this->hasMany(File::class);
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
            ->where('is_default', true)
            ->first();
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->select(['user_id', 'item_id', 'rating', 'comment', 'updated_at']);
    }
}
