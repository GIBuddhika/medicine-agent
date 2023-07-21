<?php

namespace App\Http\Handlers;

use App\Constants\ProductCategoryConstants;
use App\Constants\SessionConstants;
use App\Constants\UserRoleConstants;
use App\Constants\ValidationMessageConstants;
use App\Models\City;
use App\Models\File;
use App\Models\Item;
use App\Models\Order;
use App\Models\Shop;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ItemsHandler
{
    public function getAll($data)
    {
        $itemsQ = Item::with(['sellableItem', 'rentableItem', 'city', 'files', 'shop.file', 'personalListing.user']);

        if (isset($data['searchTerm'])) {
            $searchTerm = $data['searchTerm'];
            $itemsQ->where(function ($iquery) use ($searchTerm) {
                $iquery
                    ->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        $itemsQ->where('quantity', '>', 0);

        if (isset($data['cityId'])) {
            $cityId = $data['cityId'];
            $itemsQ->where('city_id', $cityId);
        } else if (isset($data['districtId'])) {
            $cities = City::where('district_id', $data['districtId'])->pluck('id')->toArray();
            $itemsQ->whereIn('city_id', $cities);
        }

        if ($data['page'] && $data['per_page']) {
            $totalCount = $itemsQ->count();
            $itemsQ = $itemsQ->skip(($data['page'] - 1) * $data['per_page'])
                ->take($data['per_page']);
        }

        $items = $itemsQ
            ->orderBy('created_at', 'desc')
            ->get();

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

            try {
                if ($data['is_a_shop_listing'] == true) {
                    $shopId = $data['shop_id'];
                } else {
                    //create a personal listing entry
                    $personalListingData['user_id'] = $user->id;
                    $personalListingData['address'] = $data['address'];
                    $personalListingData['latitude'] = $data['latitude'];
                    $personalListingData['longitude'] = $data['longitude'];
                    $personalListing = $this->getPersonalListingHandler()->create($personalListingData);
                }

                //save item
                $item = new Item();
                $item->is_a_shop_listing = $data['is_a_shop_listing'];
                $item->name = $data['name'];
                $item->slug = $this->generateSlug($data['name']);
                $item->category_id = $data['pricing_category'] == "sell" ? ProductCategoryConstants::Sell : ProductCategoryConstants::Rent;
                $item->quantity = $data['quantity'];

                if ($data['is_a_shop_listing'] == true) {
                    $item->shop_id = $shopId;
                    $shop = Shop::find($shopId);
                    $item->city_id = $shop->city_id;
                    $item->user_id = $shop->user_id;
                } else {
                    $item->personal_listing_id = $personalListing->id;
                    $item->city_id = $data['city_id'];
                    $item->user_id = $user->id;
                }

                if (isset($data['description'])) {
                    $item->description = $data['description'];
                }

                $item->save();
                $item = $item->fresh();
                //upload images
                $this->uploadImages($data, $user, $item);

                //set category
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
            } catch (ModelNotFoundException $th) {
                throw $th;
            } catch (ValidationException $th) {
                throw new ValidationException($th, 400);
            } catch (Exception $th) {
                dd($th);
                Log::info($th);
                throw $th;
            }
        });
    }

    public function get($slug)
    {
        try {
            $item = Item::with(['sellableItem', 'rentableItem', 'city', 'shop.city', 'user', 'files', 'shop.file', 'personalListing'])
                ->where('slug', $slug)->firstOrFail();
            return $item;
        } catch (ModelNotFoundException $th) {
            throw new NotFoundHttpException(404);
        }
    }

    public function updateItem($id, $data)
    {
        $user = session(SessionConstants::User);
        $userRole = session(SessionConstants::UserRole);

        try {
            $itemQ = Item::with('shop.shopAdmins')
                ->where('id', $id);

            if ($userRole == UserRoleConstants::SHOP_ADMIN) {
                //checking ShopAdmin has access to the shop
                $itemQ->whereHas('shop', function ($query) use ($user) {
                    $query->whereHas('shopAdmins', function ($query1) use ($user) {
                        $query1->where('user_id', $user->id);
                    });
                });
            } else {
                $itemQ->where('user_id', $user->id);
            }
            $item = $itemQ->firstOrFail();
        } catch (ModelNotFoundException $th) {
            throw new NotFoundHttpException(404);
        }

        DB::transaction(function () use ($id, $data, $item, $user) {
            $rules = [
                'is_a_shop_listing' => 'required|boolean',
                'shop_id' => 'required_if:is_a_shop_listing,true|nullable|integer|exists:shops,id',
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

            try {
                // $item->user_id = $user->id;
                $item->is_a_shop_listing = $data['is_a_shop_listing'];
                $item->name = $data['name'];
                // $item->slug = $this->generateSlug($data['name']);
                $item->category_id = $data['pricing_category'] == "sell" ? ProductCategoryConstants::Sell : ProductCategoryConstants::Rent;
                $item->quantity = $data['quantity'];

                if ($data['is_a_shop_listing'] == true) {
                    $shop = $item->shop;
                    $item->shop_id = $shop->id;
                    $item->city_id = $shop->city_id;
                } else {
                    $item->city_id = $data['city_id'];
                    $personalListing = $item->personalListing;
                    $item->personal_listing_id = $personalListing->id;
                }
                if (isset($data['description'])) {
                    $item->description = $data['description'];
                }

                $item->save();
                $item = $item->fresh();

                $imageIds = [];

                //upload main image
                $this->uploadImages($data, $user, $item, "update");
                //set category
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
                    // dd($sellableItem);


                    $item->sellableItem()->update($sellableItem);
                } else if ($item->category_id == ProductCategoryConstants::Rent) {
                    $rentableItem['item_id'] = $item->id;
                    $rentableItem['price_per_month'] = $data['price'];
                    $item->rentableItem()->update($rentableItem);
                }
                return $item->fresh();
            } catch (ModelNotFoundException $th) {
                throw $th;
            } catch (ValidationException $th) {
                throw new ValidationException($th, 400);
            } catch (Exception $th) {
                Log::info($th);
                throw $th;
            }
        });
    }

    public function deleteItem($id)
    {
        try {
            $user = session(SessionConstants::User);

            $item = Item::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();
            $item->delete();
        } catch (\Throwable $th) {
            throw new NotFoundHttpException(404);
        }
    }

    private function uploadImages($data, $user, $item, $action = "create")
    {
        $imageIds = [];

        //upload main image
        if (isset($data['image'])) {

            if ($action == "update") {
                //delete current image
                $mainImage = $item->mainImage();
                $item->files()->detach($mainImage->id);
                $mainImage->delete();
                Storage::delete("public/" . $mainImage->location);
            }

            $fileData = [
                'name' => $data['image_name'],
                'location' => "images/items/" . Carbon::now()->timestamp . $user->id,
                'image_data' => $data['image']
            ];
            $file = $this->getFilesHandler()->create($fileData);
            $item->image_id = $file->id;
            $item->save();

            $imageIds[] = $file->id;
        }

        //delete sub images
        if (isset($data['deleted_sub_images']) && $action == "update") {
            $files = $item->files->toArray();
            foreach ($data['deleted_sub_images'] as $key => $deletedImageId) {
                $fileIndex = array_search($deletedImageId, array_column($files, "id"));
                if ($fileIndex) {
                    $file = $files[$fileIndex];
                    $item->files()->detach($deletedImageId);
                    File::where('id', $deletedImageId)->delete();
                    Storage::delete("public/" . $file['location']);
                }
            }
        }

        //upload sub images
        if (isset($data['sub_images'])) {
            foreach ($data['sub_images'] as $key => $image) {
                $fileData = [
                    'name' => $image['name'],
                    'location' => "images/items/sub_images/" . Carbon::now()->timestamp . $user->id . $key,
                    'image_data' => $image['data']
                ];
                $file = $this->getFilesHandler()->create($fileData);
                $imageIds[] = $file->id;
            }
        }
        if (count($imageIds) > 0) {
            $item->files()->syncWithoutDetaching($imageIds);
        }
    }

    public function getPriceBasedOnQuantity(int $itemId, int $itemQuantity): int
    {
        $item = Item::where('id', $itemId)->with('sellableItem', 'rentableItem')->first();

        if ($item->category_id == ProductCategoryConstants::Sell) {
            if ((int) $itemQuantity >= (int)$item->sellableItem->wholesale_minimum_quantity && $item->sellableItem->wholesale_price) {
                return (int) $item->sellableItem->wholesale_price;
            }
            return (int)$item->sellableItem->retail_price;
        } else {
            return (int) $item->rentableItem->price_per_month;
        }
    }

    public function updateItemsCountAfterSuccessfulCheckout(Order $order)
    {
        $orderItems = $order->items;

        foreach ($orderItems as $orderItem) {
            $orderItem->quantity -= $orderItem->pivot->quantity;
            $orderItem->save();
        }
    }

    private function generateSlug($name)
    {
        $slugMain = str_replace(" ", "-", $name);
        $slug = $slugMain;
        $i = 2;
        while ($this->hasExistingSlug($slug)) {
            $slug = $slugMain . "-" . $i;
            $i++;
        }
        return $slug;
    }

    private function hasExistingSlug($slug)
    {
        $slugsCount = Item::where('slug', $slug)->count();
        if ($slugsCount == 0) {
            return false;
        }
        return true;
    }

    private function getPersonalListingHandler(): PersonalListingHandler
    {
        return app(PersonalListingHandler::class);
    }

    private function getFilesHandler(): FilesHandler
    {
        return app(FilesHandler::class);
    }
}
