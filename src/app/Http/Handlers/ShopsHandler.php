<?php

namespace App\Http\Handlers;

use App\Constants\SessionConstants;
use App\Constants\ValidationMessageConstants;
use App\Models\City;
use App\Models\File;
use App\Models\Shop;
use App\Rules\Phone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Stripe\StripeClient;

class ShopsHandler
{
    public function getAll()
    {
        $shops = Shop::with('city', 'file')
            ->where('is_a_personal_listing', false)
            ->get();

        return $shops;
    }

    public function getShop($id)
    {
        $shop = Shop::where('id', $id)
            ->with('city', 'file')
            ->firstOrFail();

        return $shop;
    }

    public function deleteShop($id)
    {
        $user = session(SessionConstants::User);
        $shop = Shop::where('id', $id)
            ->where('user_id', $user->id)
            ->delete();

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
            'website' => 'url|nullable',
            'latitude' => array('required', 'numeric', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'),
            'longitude' => array('required', 'numeric', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'),
            'image' => 'base64|nullable',
            'image_name' => 'required_with:image|nullable',
        ];

        $messages = [
            'required' => ValidationMessageConstants::Required,
            'integer' => ValidationMessageConstants::IntegerValue,
            'url' => ValidationMessageConstants::URL,
            'regex' => ValidationMessageConstants::Invalid,
            'exists' => ValidationMessageConstants::NotFound,
            'numeric' => ValidationMessageConstants::Invalid,
            'base64' => ValidationMessageConstants::Invalid,
            'required_with' => ValidationMessageConstants::Required,
        ];

        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator, 400);
        }

        if (isset($data['image'])) {
            $file = new File();
            $file->name = $data['image_name'];
            $file->location = "images/shops/" . Carbon::now()->timestamp . $user->id;
            $file->save();
            $image = str_replace('data:image/png;base64,', '', $data['image']);
            Storage::put("public/" . $file->location, base64_decode($image));
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
        if (isset($data['image'])) {
            $shop->file_id = $file->id;
        }
        $shop->name = $data['name'];
        $shop->slug = $slug;
        $shop->address = $data['address'];
        $shop->phone = $data['phone'];
        if (isset($data['website'])) {
            $shop->website = $data['website'];
        }
        $shop->latitude = $data['latitude'];
        $shop->longitude = $data['longitude'];

        $stripe = new StripeClient(config('app.STRIPE_SECRET'));
        $stripeCustomer =  $stripe->customers->create([
            'email' => $user->email,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'address[line1]' => $data['address'],
            'metadata' => [
                "slug" => $slug
            ]
        ]);
        $shop->stripe_customer_id = $stripeCustomer->id;

        $shop->save();
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
            'website' => 'url|nullable',
            'latitude' => array('required', 'numeric', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'),
            'longitude' => array('required', 'numeric', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'),
            'image' => 'base64|nullable',
            'image_name' => 'required_with:image|nullable',
        ];

        $messages = [
            'required' => ValidationMessageConstants::Required,
            'integer' => ValidationMessageConstants::IntegerValue,
            'url' => ValidationMessageConstants::URL,
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

        if (isset($data['image'])) {
            $file = new File();
            $file->name = $data['image_name'];
            $file->location = "images/shops/" . Carbon::now()->timestamp . $user->id;
            $file->save();
            $image = str_replace('data:image/png;base64,', '', $data['image']);
            Storage::put("public/" . $file->location, base64_decode($image));
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
        if (isset($data['image'])) {
            $shop->file_id = $file->id;
        }
        $shop->name = $data['name'];
        $shop->slug = $slug;
        $shop->address = $data['address'];
        $shop->phone = $data['phone'];
        if (isset($data['website'])) {
            $shop->website = $data['website'];
        }
        $shop->latitude = $data['latitude'];
        $shop->longitude = $data['longitude'];

        if ($shop->isDirty('name') || $shop->isDirty('phone') || $shop->isDirty('address')) {
            $stripe = new StripeClient(config('app.STRIPE_SECRET'));
            $stripe->customers->update($shop->stripe_customer_id, [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'address[line1]' => $data['address'],
            ]);
        }

        $shop->save();
        return $shop;
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
