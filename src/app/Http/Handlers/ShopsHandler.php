<?php

namespace App\Http\Handlers;

use App\Constants\SessionConstants;
use App\Constants\UserRoleConstants;
use App\Constants\ValidationMessageConstants;
use App\Models\City;
use App\Models\File;
use App\Models\Item;
use App\Models\Shop;
use App\Rules\Phone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Stripe\StripeClient;

class ShopsHandler
{
    public function getAll()
    {
        $shops = Shop::with('city')
            ->where('is_a_personal_listing', false)
            ->get();

        return $shops;
    }

    public function getShop($id)
    {
        $shop = Shop::where('id', $id)
            ->with('city')
            ->firstOrFail();

        return $shop;
    }

    public function deleteShop($id)
    {
        $user = session(SessionConstants::User);
        $shop = Shop::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        Storage::delete("public/" . $shop->image_path);

        $shop->delete();

        return $shop;
    }

    public function createShop($data)
    {
        $user = session(SessionConstants::User);
        $rules = [
            'city_id' => 'required|integer|exists:cities,id',
            'name' => 'required',
            'address' => 'required',
            'phone' => ['required', 'numeric', new Phone],
            'website' => 'nullable',
            'latitude' => array('required', 'numeric', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'),
            'longitude' => array('required', 'numeric', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'),
            'image' => 'base64|nullable',
            'image_name' => 'required_with:image|nullable',
            'shop_admin_ids' => 'array',
        ];

        $messages = [
            'required' => ValidationMessageConstants::Required,
            'integer' => ValidationMessageConstants::IntegerValue,
            'regex' => ValidationMessageConstants::Invalid,
            'exists' => ValidationMessageConstants::NotFound,
            'numeric' => ValidationMessageConstants::Invalid,
            'base64' => ValidationMessageConstants::Invalid,
            'required_with' => ValidationMessageConstants::Required,
            'array' => ValidationMessageConstants::Invalid,
        ];

        $data['shop_admin_ids'] = json_decode($data['shop_admin_ids']);

        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator, 400);
        }

        $city = City::find($data['city_id']);
        $slugMain = str_replace(" ", "-", $data['name']) . "-" . $city->name;
        $slug = $slugMain;
        $i = 2;
        while ($this->hasExistingSlug($slug)) {
            $slug = $slugMain . "-" . $i;
            $i++;
        }

        $shop = new Shop();
        $shop->user_id = $user->id;
        $shop->city_id = $data['city_id'];
        $shop->name = $data['name'];
        $shop->slug = $slug;
        $shop->address = $data['address'];
        $shop->phone = $data['phone'];
        $shop->latitude = $data['latitude'];
        $shop->longitude = $data['longitude'];

        if (isset($data['website'])) {
            $shop->website = $data['website'];
        }

        if (isset($data['image'])) {
            $image_path = "images/shops/" . Carbon::now()->timestamp . $user->id;
            $image = str_replace('data:image/png;base64,', '', $data['image']);
            Storage::put("public/" . $image_path, base64_decode($image));
            $shop->image_path = $image_path;
        }

        $shop->save();

        $shop->shopAdmins()->attach($data['shop_admin_ids']);

        return $shop->fresh();
    }

    public function updateShop($id, $data)
    {
        $user = session(SessionConstants::User);
        $shop = Shop::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $rules = [
            'city_id' => 'required|integer|exists:cities,id',
            'name' => 'required',
            'address' => 'required',
            'phone' => ['required', 'numeric', new Phone],
            'website' => 'nullable',
            'latitude' => array('required', 'numeric', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'),
            'longitude' => array('required', 'numeric', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'),
            'image' => 'base64|nullable',
            'image_name' => 'required_with:image|nullable',
        ];

        $messages = [
            'required' => ValidationMessageConstants::Required,
            'integer' => ValidationMessageConstants::IntegerValue,
            'regex' => ValidationMessageConstants::Invalid,
            'exists' => ValidationMessageConstants::NotFound,
            'numeric' => ValidationMessageConstants::Invalid,
            'image' => 'base64|nullable',
            'image_name' => 'required_with:image|nullable',
        ];

        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator, 400);
        }

        $city = City::find($data['city_id']);
        $slugMain = str_replace(" ", "-", $data['name']) . "-" . $city->name;
        $slug = $slugMain;
        $i = 2;
        while ($this->hasExistingSlug($slug) && $slug != $shop->slug) {
            $slug = $slugMain . "-" . $i;
            $i++;
        }

        $shop->city_id = $data['city_id'];
        $shop->name = $data['name'];
        $shop->slug = $slug;
        $shop->address = $data['address'];
        $shop->phone = $data['phone'];
        $shop->latitude = $data['latitude'];
        $shop->longitude = $data['longitude'];

        if (isset($data['website'])) {
            $shop->website = $data['website'];
        }

        if (isset($data['image'])) {
            $image_path = "images/shops/" . Carbon::now()->timestamp . $user->id;
            $image = str_replace('data:image/png;base64,', '', $data['image']);
            Storage::put("public/" . $image_path, base64_decode($image));
            $shop->image_path = $image_path;
        }

        $shop->save();

        $data['shop_admin_ids'] = json_decode($data['shop_admin_ids']);
        $shop->shopAdmins()->detach();
        $shop->shopAdmins()->attach($data['shop_admin_ids']);

        return $shop;
    }

    public function geItems($shopId)
    {
        $user = session(SessionConstants::User);
        $userRole = session(SessionConstants::UserRole);

        $itemsQ = Item::with('shop');

        if ($userRole == UserRoleConstants::SHOP_ADMIN) {
            //checking ShopAdmin has access to the shop
            $itemsQ->whereHas('shop', function ($query) use ($user) {
                $query->whereHas('shopAdmins', function ($query1) use ($user) {
                    $query1->where('user_id', $user->id);
                });
            });
        } else {
            $itemsQ->where('user_id', $user->id);
        }

        $itemsQ->where('shop_id', $shopId)
            ->get();

        return $itemsQ->get();
    }

    private function hasExistingSlug($slug)
    {
        $slugsCount = Shop::where('slug', $slug)->count();
        if ($slugsCount == 0) {
            return false;
        }
        return true;
    }
}
