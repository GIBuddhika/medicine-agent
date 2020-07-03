<?php

namespace App\Http\Handlers;

use App\Constants\SessionConstants;
use App\Constants\ValidationMessageConstants;
use App\Models\File;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ShopsHandler
{
    public function getAll()
    {
        $shops = Shop::with('city')
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
            ->delete();

        return $shop;
    }

    public function createShop($data)
    {
        $rules = [
            'city_id' => 'required|integer|exists:cities,id',
            'name' => 'required',
            'address' => 'required',
            'phone' => 'required|numeric',
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
            $file->location = "images/shops/" . Carbon::now()->timestamp;
            $file->save();
            $image = str_replace('data:image/png;base64,', '', $data['image']);
            Storage::put($file->location, base64_decode($image));
        }

        $shop = new Shop();
        $shop->user_id = session(SessionConstants::User)->id;
        $shop->city_id = $data['city_id'];
        if (isset($data['image'])) {
            $shop->file_id = $file->id;
        }
        $shop->name = $data['name'];
        $shop->address = $data['address'];
        $shop->phone = $data['phone'];
        if (isset($data['website'])) {
            $shop->website = $data['website'];
        }
        $shop->latitude = $data['latitude'];
        $shop->longitude = $data['longitude'];

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
            'phone' => 'required|numeric',
            'website' => 'url',
            'latitude' => array('required', 'numeric', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'),
            'longitude' => array('required', 'numeric', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'),
        ];

        $messages = [
            'required' => ValidationMessageConstants::Required,
            'integer' => ValidationMessageConstants::IntegerValue,
            'url' => ValidationMessageConstants::URL,
            'regex' => ValidationMessageConstants::Invalid,
            'exists' => ValidationMessageConstants::NotFound,
            'numeric' => ValidationMessageConstants::Invalid,
        ];

        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator, 400);
        }

        $shop->user_id = session(SessionConstants::User)->id;
        $shop->city_id = $data['city_id'];
        $shop->name = $data['name'];
        $shop->address = $data['address'];
        $shop->phone = $data['phone'];
        if (isset($data['website'])) {
            $shop->website = $data['website'];
        }
        $shop->latitude = $data['latitude'];
        $shop->longitude = $data['longitude'];

        $shop->save();
        return $shop;
    }
}
