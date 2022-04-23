<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SellableItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'item_id','retail_price','wholesale_price','wholesale_minimum_quantity'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
