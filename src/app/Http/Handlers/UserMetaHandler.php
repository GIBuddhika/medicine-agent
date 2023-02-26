<?php

namespace App\Http\Handlers;

use App\Constants\SessionConstants;
use App\Models\UserMeta;

class UserMetaHandler
{
    public function create($data)
    {
        $user = session(SessionConstants::User);
        $userMeta = new UserMeta();

        $userMeta->user_id = $user->id;
        $userMeta->key = $data['key'];
        $userMeta->value = $data['value'];
        $userMeta->save();
        return $userMeta->fresh();
    }
}
