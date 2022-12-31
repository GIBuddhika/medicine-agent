<?php

namespace App\Rules;

use App\Constants\ValidationMessageConstants;
use Illuminate\Contracts\Validation\Rule;

class Phone implements Rule
{
    public function passes($attribute, $value)
    {
        if (strlen($value) !== 9) {
            return false;
        }
        if (array_search(substr($value, 0, 2), ["70", "71", "72", "74", "75", "76", "77", "78"]) === false) {
            return false;
        }
        return true;
    }

    public function message()
    {
        return ValidationMessageConstants::Invalid;
    }
}
