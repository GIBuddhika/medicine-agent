<?php

namespace App\Http\Handlers;

use App\Constants\SessionConstants;
use App\Models\Item;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UsersHandler
{
    public function get(int $userId)
    {
        $user = session(SessionConstants::User);
        if ($user->id == $userId) {
            $user = User::where('id', $userId)
                ->firstOrFail();
            return $user;
        } else {
            throw new ModelNotFoundException();
        }
    }

    public function getShops(int $userId, array $data)
    {
        $user = session(SessionConstants::User);
        if ($user->id == $userId) {
            $shops = Shop::with(['city', 'file', 'shopAdmins'])
                ->where('user_id', $userId)
                // ->where('is_a_personal_listing', filter_var($data['is_a_personal_listing'], FILTER_VALIDATE_BOOLEAN))
                ->get();
            return $shops;
        } else {
            throw new ModelNotFoundException();
        }
    }

    public function getItems(int $userId, array $data)
    {
        $user = session(SessionConstants::User);
        if ($user->id == $userId) {
            $itemsQ = Item::with(['sellableItem', 'rentableItem', 'shop.city', 'files'])
                ->where('user_id', $userId);

            if (isset($data['searchTerm'])) {
                $searchTerm = $data['searchTerm'];
                $itemsQ->where(function ($iquery) use ($searchTerm) {
                    $iquery
                        ->where('name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('description', 'like', '%' . $searchTerm . '%');
                });
            }

            if (isset($data['type'])) {
                if ($data['type'] == "shop") {
                    $itemsQ->where('is_a_shop_listing', true);
                } elseif ($data['type'] == "personal") {
                    $itemsQ->where('is_a_shop_listing', false);
                }
            }

            if (isset($data['shopId'])) {
                $shopId = $data['shopId'];
                //check if user can access to the shop
                $itemsQ->where(function ($iquery) use ($shopId) {
                    $iquery
                        ->where('shop_id', $shopId);
                });
            }

            if ($data['page'] && $data['per_page']) {
                $totalCount = $itemsQ->count();
                $itemsQ = $itemsQ->skip(($data['page'] - 1) * $data['per_page'])
                    ->take($data['per_page']);
            }

            $items = $itemsQ->get();
            return [
                'data' => $items,
                'total' => $totalCount,
            ];
        } else {
            throw new ModelNotFoundException();
        }
    }

    public function getShopAdmins(int $userId)
    {
        $user = session(SessionConstants::User);
        if ($user->id == $userId) {
            $shopAdmins = User::with(['shops'])
                ->where('owner_id', $userId)
                ->get();
            return $shopAdmins;
        } else {
            throw new ModelNotFoundException();
        }
    }
}
