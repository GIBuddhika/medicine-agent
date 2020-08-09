<?php

namespace App\Http\Handlers;

use App\Constants\ProductCategoryConstants;
use App\Constants\SessionConstants;
use App\Constants\ValidationMessageConstants;
use App\Models\City;
use App\Models\File;
use App\Models\Item;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ItemsHandler
{
    public function getAll($data)
    {
        $itemsQ = Item::with(['sellableItem', 'rentableItem', 'shop.file', 'shop.city', 'files']);

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


    public function createItem($data)
    {
        DB::transaction(function () use ($data) {
            $user = session(SessionConstants::User);
            $rules = [
                'is_a_shop_listing' => 'required|boolean',
                'shop_id' => 'required_if:is_a_shop_listing,true|nullable|integer|exists:shops,id',
                'city_id' => 'required_if:is_a_shop_listing,false|integer|exists:cities,id|nullable',
                'address' => 'required_if:is_a_shop_listing,false|nullable',
                'phone' => 'required_if:is_a_shop_listing,false|nullable|numeric',
                'latitude' => array('required_if:is_a_shop_listing,false', 'numeric', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'),
                'longitude' => array('required_if:is_a_shop_listing,false', 'numeric', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'),
                'name' => 'required',
                'quantity' => 'required|numeric',
                'price' => 'required_without:wholesale_price|numeric|nullable',
                'pricing_category' => 'required',
                'image' => 'base64|nullable',
                'image_name' => 'required_with:image|nullable',
                'sub_images.*.data' => 'base64|nullable',
                'sub_images.*.name' => 'required_with:sub_images.*.data|nullable',
                'min_quantity' => 'required_if:is_wholesale_pricing_enabled,true|numeric|nullable',
                'wholesale_price' => 'required_if:is_wholesale_pricing_enabled,true|numeric|nullable',
            ];

            $messages = [
                'required' => ValidationMessageConstants::Required,
                'integer' => ValidationMessageConstants::IntegerValue,
                'exists' => ValidationMessageConstants::NotFound,
                'numeric' => ValidationMessageConstants::Invalid,
                'base64' => ValidationMessageConstants::Invalid,
                'required_with' => ValidationMessageConstants::Required,
                'required_without' => ValidationMessageConstants::Required,
                'required_if' => ValidationMessageConstants::Required,
            ];

            $validator = Validator::make($data, $rules, $messages);
            if ($validator->fails()) {
                throw new ValidationException($validator, 400);
            }

            if ($data['is_a_shop_listing'] == false) {
                $shop = new Shop();
                $shop->user_id = $user->id;
                $shop->city_id = $data['city_id'];
                $shop->is_a_personal_listing = true;
                $shop->phone = $data['phone'];
                $shop->address = $data['address'];
                $shop->latitude = $data['latitude'];
                $shop->longitude = $data['longitude'];
                $shop->save();
                $shopId = $shop->id;
            } else {
                $shopId = $data['shop_id'];
            }

            $slugMain = str_replace(" ", "-", $data['name']);
            $slug = $slugMain;
            $i = 2;
            while ($this->hasExistingSlug($slug)) {
                $slug = $slugMain . "-" . $i;
                $i++;
            }

            $item = new Item();
            $item->user_id = $user->id;
            $item->shop_id = $shopId;
            $item->is_a_shop_listing = $data['is_a_shop_listing'];
            $item->name = $data['name'];
            $item->slug = $slug;
            if (isset($data['description'])) {
                $item->description = $data['description'];
            }
            $item->category_id = $data['pricing_category'] == "sell" ? ProductCategoryConstants::Sell : ProductCategoryConstants::Rent;
            $item->quantity = $data['quantity'];
            $item->save();

            $item = $item->fresh();

            $imageIds = [];

            //upload main image
            if (isset($data['image'])) {
                $file = new File();
                $file->name = $data['image_name'];
                $file->location = "images/items/" . Carbon::now()->timestamp . $user->id;
                $file->save();
                $image = str_replace('data:image/png;base64,', '', $data['image']);
                $image = str_replace('data:image/jpeg;base64,', '', $image);
                Storage::put("public/" . $file->location, base64_decode($image));

                $item->image_id = $file->id;
                $item->save();

                $imageIds[] = $file->id;
            }

            //upload sub images
            if (isset($data['sub_images'])) {
                foreach ($data['sub_images'] as $key => $image) {
                    $file = new File();
                    $file->name = $image['name'];
                    $file->location = "images/items/sub_images/" . Carbon::now()->timestamp . $user->id . $key;
                    $file->save();
                    $imageData = str_replace('data:image/png;base64,', '', $image['data']);
                    $processedImage = str_replace('data:image/jpeg;base64,', '', $imageData);
                    Storage::put("public/" . $file->location, base64_decode($processedImage));
                    $imageIds[] = $file->id;
                }
            }
            if (count($imageIds) > 0) {
                $item->files()->sync($imageIds);
            }

            if ($item->category_id == ProductCategoryConstants::Sell) {
                $sellableItem['item_id'] = $item->id;
                if (isset($data['price'])) {
                    $sellableItem['retail_price'] = $data['price'];
                }
                if (isset($data['wholesale_price'])) {
                    $sellableItem['wholesale_price'] = $data['wholesale_price'];
                }
                if (isset($data['min_quantity'])) {
                    $sellableItem['wholesale_minimum_quantity'] = $data['min_quantity'];
                }
                $item->sellableItem()->create($sellableItem);
            } else if ($item->category_id == ProductCategoryConstants::Rent) {
                $rentableItem['item_id'] = $item->id;
                $rentableItem['price_per_month'] = $data['price'];
                $item->rentableItem()->create($rentableItem);
            }
            return $item->fresh();
        });
    }

    private function hasExistingSlug($slug)
    {
        $slugsCount = Item::where('slug', $slug)->count();
        if ($slugsCount == 0) {
            return false;
        }
        return true;
    }
}
