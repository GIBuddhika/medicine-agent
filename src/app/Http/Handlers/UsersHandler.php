<?php

namespace App\Http\Handlers;

use App\Constants\SessionConstants;
use App\Constants\UserRoleConstants;
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
            if ($user->is_admin) {
                if ($user->owner_id != null) {
                    $shops = $user->shops()->with(['city', 'file'])->get();
                } else {
                    $shops = Shop::with(['city', 'file', 'shopAdmins'])
                        ->where('user_id', $userId)
                        ->get();
                }
            }
            return $shops;
        } else {
            throw new ModelNotFoundException();
        }
    }

    public function getItems(int $userId, array $data)
    {
        $user = session(SessionConstants::User);
        $userRole = session(SessionConstants::UserRole);

        if ($userRole != UserRoleConstants::SHOP_ADMIN && $user->id != $userId) {
            throw new ModelNotFoundException();
        }

        $itemsQ = Item::with(['sellableItem', 'rentableItem', 'city', 'files', 'personalListing', 'shop.shopAdmins']);

        //check this function again.
        if ($userRole == UserRoleConstants::SHOP_ADMIN) {
            //checking ShopAdmin has access to the shop
            $itemsQ->whereHas('shop', function ($query) use ($user) {
                $query->whereHas('shopAdmins', function ($query1) use ($user) {
                    $query1->where('user_id', $user->id);
                });
            });
        } else {
            $itemsQ->where('user_id', $userId);
        }

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
            $itemsQ->where('shop_id', $shopId);
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

    public function getPersonalItems(int $userId)
    {
        $user = session(SessionConstants::User);
        if ($user->id != $userId) {
            throw new ModelNotFoundException();
        }

        $items = Item::with(['sellableItem', 'rentableItem', 'city', 'files', 'personalListing'])
            ->where('is_a_shop_listing', false)
            ->where('user_id', $userId)
            ->get();

        return [
            'data' => $items,
            'total' => count($items),
        ];
    }
}
