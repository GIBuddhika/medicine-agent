<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    public function items()
    {
        return $this->belongsToMany(Item::class)->withPivot('id', 'price', 'quantity', 'duration')->with('sellableItem');
    }
}
