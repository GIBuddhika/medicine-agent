<?php

namespace App\Rules;

use App\Constants\ProductCategoryConstants;
use App\Constants\ValidationMessageConstants;
use App\Models\Item;
use Illuminate\Contracts\Validation\Rule;

class RequiredIfARentableItem implements Rule
{
    public function passes($attribute, $value)
    {
        $index = explode('.', $attribute)[1];
        $itemId = request()->input("data.{$index}.item_id");

        $item = Item::where('id', $itemId)
            ->where('category_id', ProductCategoryConstants::Rent)
            ->exists();

        if ($item) {
            return $value ? true : false;
        }
        return true;
    }

    public function message()
    {
        return ValidationMessageConstants::Required;
    }
}
