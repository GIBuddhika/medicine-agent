<?php

namespace App\Rules;

use App\Constants\ValidationMessageConstants;
use App\Models\Item;
use Illuminate\Contracts\Validation\Rule;

class IsQuantityAvailable implements Rule
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function passes($attribute, $value)
    {
        $index = explode('.', $attribute)[1];

        $itemId = $this->data[$index]['item_id'];
        $quantity = $this->data[$index]['quantity'];

        return Item::where('id', $itemId)
            ->where('quantity', '>=', $quantity)
            ->exists();
    }

    public function message()
    {
        return ValidationMessageConstants::Invalid;
    }
}
