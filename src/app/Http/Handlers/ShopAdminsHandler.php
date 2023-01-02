<?php

namespace App\Http\Handlers;

use App\Constants\SessionConstants;
use App\Constants\ValidationMessageConstants;
use App\Models\Item;
use App\Models\Shop;
use App\Models\User;
use App\Rules\Phone;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShopAdminsHandler
{
    public function create(array $data)
    {
        $rules = [
            'name' => 'required',
            'phone' => ['required', 'numeric', new Phone],
            'email' => 'required|unique:users,email,null,id,is_admin,1,deleted_at,NULL',
            'password' => 'required|confirmed',
            'shop_ids' => 'array',
        ];
        $messages = [
            'required' => ValidationMessageConstants::Required,
            'confirmed' => ValidationMessageConstants::Confirmed,
            'unique' => ValidationMessageConstants::Duplicate,
            'numeric' => ValidationMessageConstants::Invalid,
            'array' => ValidationMessageConstants::Invalid,
        ];
        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator, 400);
        }

        try {
            $user = new User();
            $user->name = $data['name'];
            $user->phone = $data['phone'];
            $user->email = $data['email'];
            $user->password = Hash::make($data['password']);
            $user->is_admin = 1;
            $user->owner_id = session(SessionConstants::User)->id;
            $user->save();

            $user->shops()->attach($data['shop_ids']);

            return $user;
        } catch (\Exception $th) {
            throw $th;
        }
    }

    public function update(int $id, array $data)
    {
        $rules = [
            'name' => 'required',
            'phone' => ['required', 'numeric', new Phone],
            'email' => 'required|unique:users,email,' . $id . ',id,is_admin,1',
            'password' => 'confirmed',
            'shop_ids' => 'array',
        ];
        $messages = [
            'required' => ValidationMessageConstants::Required,
            'confirmed' => ValidationMessageConstants::Confirmed,
            'unique' => ValidationMessageConstants::Duplicate,
            'numeric' => ValidationMessageConstants::Invalid,
            'array' => ValidationMessageConstants::Invalid,
        ];
        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator, 400);
        }

        try {
            $currentUser = session(SessionConstants::User);

            $shopAdmin = User::where('id', $id)
                ->where('owner_id', $currentUser->id)
                ->where('is_admin', 1)
                ->firstOrFail();

            $shopAdmin->name = $data['name'];
            $shopAdmin->phone = $data['phone'];
            $shopAdmin->email = $data['email'];
            if ($data['password'] != null) {
                $shopAdmin->password = Hash::make($data['password']);
            }
            $shopAdmin->save();

            $shopAdmin->shops()->sync($data['shop_ids']);

            return $shopAdmin;
        } catch (ModelNotFoundException $th) {
            throw $th;
        } catch (\Exception $th) {
            throw $th;
        }
    }

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

    public function delete(int $shopAdminId)
    {
        try {
            $user = session(SessionConstants::User);
            $user = User::where('id', $shopAdminId)
                ->where('owner_id', $user->id)
                ->where('is_admin', 1)
                ->firstOrFail();

            $user->delete();
            return true;
        } catch (ModelNotFoundException $th) {
            throw $th;
        }
    }

    public function all()
    {
        try {
            $user = session(SessionConstants::User);

            $shopAdmins = User::with('shops', 'shops.city')
                ->where('owner_id', $user->id)
                ->get();

            return $shopAdmins;
        } catch (\Exception $th) {
            throw $th;
        }
    }

    public function getShops(int $userId)
    {
        $user = session(SessionConstants::User);
        if ($user->id == $userId) {
            $shops = Shop::with(['city', 'file'])
                ->where('user_id', $userId)
                ->where('is_a_personal_listing', false)
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
            $itemsQ = Item::with(['sellableItem', 'rentableItem', 'shop.city'])
                ->where('user_id', $userId);

            if (isset($data['searchTerm'])) {
                $searchTerm = $data['searchTerm'];
                $itemsQ->where(function ($iquery) use ($searchTerm) {
                    $iquery
                        ->where('name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('description', 'like', '%' . $searchTerm . '%');
                });
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
}
