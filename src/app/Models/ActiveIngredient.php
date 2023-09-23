<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActiveIngredient extends Model
{
    protected $hidden = ['created_at', 'updated_at'];
    protected $fillable = ['active_ingredient_id'];

    public function items()
    {
        return $this->belongsToMany(Item::class);
    }
}
