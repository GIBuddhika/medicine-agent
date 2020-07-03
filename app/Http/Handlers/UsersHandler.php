<?php

namespace App\Http\Handlers;

use App\Constants\SessionConstants;
use App\Models\Shop;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UsersHandler
{
    public function getShops(int $userId)
    {
        $user = session(SessionConstants::User);
        if ($user->id == $userId) {
            $shops = Shop::with('city')
                ->where('user_id', $userId)
                ->get();
            return $shops;
        } else {
            throw new ModelNotFoundException();
        }
    }
}
