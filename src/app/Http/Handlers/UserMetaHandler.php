<?php

namespace App\Http\Handlers;

use App\Constants\SessionConstants;
use App\Constants\UserMetaConstants;
use App\Models\UserMeta;

class UserMetaHandler
{
    private $availableMetaKeys;
    public function __construct()
    {
        $this->availableMetaKeys = [
            UserMetaConstants::StripeCustomerId,
            UserMetaConstants::Bank,
            UserMetaConstants::Branch,
            UserMetaConstants::AccountNumber,
            UserMetaConstants::AccountName,
        ];
    }

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

    public function updateOrCreate($data)
    {
        $user = session(SessionConstants::User);

        foreach ($data as $userMetaKey => $userMetaValue) {

            if (!in_array($userMetaKey, $this->availableMetaKeys)) {
                return false;
            }

            $bankUserMeta = UserMeta::where('user_id', $user->id)
                ->where('key', $userMetaKey)
                ->first();

            if ($bankUserMeta) {
                $bankUserMeta->value = $userMetaValue;
                $bankUserMeta->save();
            } else {
                $this->create([
                    'key' => $userMetaKey,
                    'value' => $userMetaValue
                ]);
            }
        }
    }
}
