<?php

namespace App\Rules;

use App\Constants\ValidationMessageConstants;
use App\Models\Item;
use Illuminate\Contracts\Validation\Rule;

class IsQuantityAvailable implements Rule
{
    public function passes($attribute, $value)
    {
        $index = explode('.', $attribute)[1];

        $itemId = request()->input("data.{$index}.item_id");
        $quantity = request()->input("data.{$index}.quantity");

        return Item::where('id', $itemId)
            ->where('quantity', '>=', $quantity)
            ->exists();
    }

    public function message()
    {
        return ValidationMessageConstants::Invalid;
    }
}
