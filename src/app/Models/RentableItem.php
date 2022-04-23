<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentableItem extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'item_id', 'price_per_month'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
