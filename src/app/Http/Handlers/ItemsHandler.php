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
use App\Models\Review;
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
        $searchTermForMYSQL = "";
        if (isset($data['searchTerm'])) {
            $searchTermForMYSQL = implode(' ', array_map(fn ($text) => '+' . $text . '*', explode(' ', $data['searchTerm'])));
        }

        $items = DB::select(
            DB::raw(
                "
                SELECT distinct items.id, items.created_at
                
                " . ((isset($data['searchTerm'])) ? ",MATCH(items.name) AGAINST('" . $searchTermForMYSQL . "' IN BOOLEAN MODE) as relevance " : "") . "
                
                FROM items 

                join cities on items.city_id=cities.id
                join districts on cities.district_id=districts.id
                left join active_ingredient_item on active_ingredient_item.item_id=items.id
                left join active_ingredients on active_ingredient_item.active_ingredient_id=active_ingredients.id

                WHERE quantity > 0 

                " . ((isset($data['searchTerm'])) ? "AND MATCH(items.name) AGAINST('" . $searchTermForMYSQL . "' IN BOOLEAN MODE) " : "") . "
                " . ((isset($data['genericName'])) ? "AND active_ingredients.name = '" . $data['genericName'] . "' " : "") . "
                " . ((isset($data['cityId'])) ? "AND items.city_id = " . $data['cityId'] . " " : "") . "
                " . ((isset($data['districtId'])) ? "AND districts.id = " . $data['districtId'] . " " : "") . "

                " . ((isset($data['searchTerm'])) ? "ORDER BY relevance DESC" : "ORDER BY items.created_at DESC") . "
                "
            )
        );

        $ids = array_map(fn ($item) => $item->id, $items);

        $itemsQ = Item::with(['sellableItem', 'rentableItem', 'city', 'files', 'shop', 'personalListing.user', 'reviews.user', 'brand', 'activeIngredients'])
            ->whereIn('id', $ids);

        if ($data['page'] && $data['per_page']) {
            $totalCount = $itemsQ->count();
            $itemsQ = $itemsQ->skip(($data['page'] - 1) * $data['per_page'])
                ->take($data['per_page']);
        }

        return [
            'data' => $itemsQ->get(),
            'total' => $totalCount,
        ];
    }

    public function getSimilarProducts($data)
    {
        $searchTermForMYSQL = "";
        //convert `test search` to `test*|search*`
        if (isset($data['searchTerm'])) {
            $searchTermForMYSQL = implode('|', array_map(fn ($text) => $text . '*', explode(' ', $data['searchTerm'])));
        }

        //search using wildcard
        $items = DB::select(
            DB::raw(
                "
                SELECT distinct items.id, items.created_at
                
                " . ((isset($data['searchTerm'])) ? ",MATCH(items.name,items.description) AGAINST('" . $searchTermForMYSQL . "' IN BOOLEAN MODE) as relevance " : "") . "
                
                FROM items 

                join cities on items.city_id=cities.id
                join districts on cities.district_id=districts.id
                left join active_ingredient_item on active_ingredient_item.item_id=items.id
                left join active_ingredients on active_ingredient_item.active_ingredient_id=active_ingredients.id

                WHERE quantity > 0 

                " . ((isset($data['searchTerm'])) ? "AND MATCH(items.name,items.description) AGAINST('" . $searchTermForMYSQL . "' IN BOOLEAN MODE) " : "") . "
                " . ((isset($data['genericName'])) ? "AND active_ingredients.name = '" . $data['genericName'] . "' " : "") . "
                " . ((isset($data['cityId'])) ? "AND items.city_id = " . $data['cityId'] . " " : "") . "
                " . ((isset($data['districtId'])) ? "AND districts.id = " . $data['districtId'] . " " : "") . "

                " . ((isset($data['searchTerm'])) ? "ORDER BY relevance DESC" : "ORDER BY items.created_at DESC") . "
                " . ((isset($data['page']) && isset($data['per_page'])) ? "LIMIT " . $data['per_page'] . " OFFSET " . ($data['page'] - 1) * $data['per_page'] : "") . "
                "
            )
        );

        $totalCount = count($items);

        if ($totalCount > 0) {
            $ids = array_map(fn ($item) => $item->id, $items);

            $itemsQ = Item::with(['sellableItem', 'rentableItem', 'city', 'files', 'shop', 'personalListing.user', 'reviews.user', 'brand', 'activeIngredients'])
                ->whereIn('id', $ids);

            $items =  $itemsQ->get();
        } else {
            $itemsQ = Item::with(['sellableItem', 'rentableItem', 'city', 'files', 'shop', 'personalListing.user', 'reviews.user', 'brand', 'activeIngredients']);

            if (isset($data['cityId'])) {
                $cityId = $data['cityId'];
                $itemsQ->where('city_id', $cityId);
            } elseif (isset($data['districtId'])) {
                $cities = City::where('district_id', $data['districtId'])->pluck('id')->toArray();
                $itemsQ->whereIn('city_id', $cities);
            }

            if (isset($data['genericName'])) {
                //If search query has genericName, search for matching genericName
                $genericName = $data['genericName'];
                $itemsQ->whereHas('activeIngredients', function ($q) use ($genericName) {
                    $q->where('name', $genericName);
                });
            } elseif (isset($data['searchTerm'])) {
                //If no genericName present in search query but searchTerm present, search for items.name, items.description, and activeIngredients.name for results.
                $searchTerm = $data['searchTerm'];
                $itemsQ->where(function ($iquery) use ($searchTerm) {
                    $iquery
                        ->orwhere('name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('description', 'like', '%' . $searchTerm . '%');
                    $iquery->orwhereHas('activeIngredients', function ($q) use ($searchTerm) {
                        $q->where('name',  'like', '%' . $searchTerm . '%');
                    });
                });
            }

            $itemsQ->where('quantity', '>', 0);

            if ($data['page'] && $data['per_page']) {
                $totalCount = $itemsQ->count();
                $itemsQ = $itemsQ->skip(($data['page'] - 1) * $data['per_page'])
                    ->take($data['per_page']);
            }

            $items = $itemsQ
                ->orderBy('created_at', 'desc')
                ->get();

            if ($totalCount == 0) {
                if (isset($data['searchTerm'])) {

                    $items = DB::select(
                        DB::raw(
                            "
                            SELECT distinct items.id, items.created_at ,MATCH(items.name,items.description) AGAINST('" . $searchTermForMYSQL . "' IN BOOLEAN MODE) as relevance
                            FROM items
                            WHERE MATCH(items.name,items.description) AGAINST('" . $searchTermForMYSQL . "' IN BOOLEAN MODE)
                            ORDER BY relevance DESC
                            "
                        )
                    );

                    $firstTwoItems = array_slice($items, 0, 2);
                    $firstTwoItemIds = array_map(fn ($item) => $item->id, $firstTwoItems);

                    // dd($firstTwoItems);

                    $activeIngredients = [];

                    $itemsCollection  = Item::with(['activeIngredients'])
                        ->whereIn('id', $firstTwoItemIds)
                        ->get();

                    foreach ($itemsCollection as $item) {
                        $activeIngredientsCollection = $item->activeIngredients;
                        foreach ($activeIngredientsCollection as $activeIngredient) {
                            $activeIngredients[] = $activeIngredient->name;
                        }
                    }

                    // Step 1: Count the occurrences
                    $counts = array_count_values($activeIngredients);

                    // Step 2: Sort by frequency
                    uasort($counts, function ($a, $b) {
                        return $b - $a; // Sort in descending order
                    });

                    // Step 3: Remove duplicates
                    $allActiveIngredients = [];
                    foreach ($activeIngredients as $item) {
                        if (isset($counts[$item])) {
                            $allActiveIngredients[] = $item;
                            unset($counts[$item]);
                        }
                    }

                    if (count($allActiveIngredients) > 0) {

                        $itemsQ = Item::with(['sellableItem', 'rentableItem', 'city', 'files', 'shop', 'personalListing.user', 'reviews.user', 'brand', 'activeIngredients'])
                            ->whereHas('activeIngredients', function ($q) use ($allActiveIngredients) {
                                $q->whereIn('name', $allActiveIngredients);
                            });

                        if (isset($data['cityId'])) {
                            $cityId = $data['cityId'];
                            $itemsQ->where('city_id', $cityId);
                        } elseif (isset($data['districtId'])) {
                            $cities = City::where('district_id', $data['districtId'])->pluck('id')->toArray();
                            $itemsQ->whereIn('city_id', $cities);
                        }

                        $itemsQ->where('quantity', '>', 0);

                        if ($data['page'] && $data['per_page']) {
                            $totalCount = $itemsQ->count();
                            $itemsQ = $itemsQ->skip(($data['page'] - 1) * $data['per_page'])
                                ->take($data['per_page']);
                        }

                        $items = $itemsQ
                            ->orderBy('created_at', 'desc')
                            ->get();
                    }
                }
            }
        }
        return [
            'data' => $items,
            'total' => $totalCount,
        ];
    }

    public function createItem($data)
    {
        return DB::transaction(function () use ($data) {
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
                'brand' => 'required',
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

                //take brand id from brands table
                $brand = $this->getBrandsHandler()->getOrCreateBrand($data['brand']);
                $item->brand_id = $brand->id;

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

                //set active ingredients
                $activeIngredientIds = $this->getActiveIngredientsHandler()->getOrCreateActiveIngredients($data['active_ingredients']);
                $item->activeIngredients()->sync($activeIngredientIds);

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
            $item = Item::with(['sellableItem', 'rentableItem', 'city', 'shop.city', 'user', 'files', 'shop', 'personalListing', 'reviews.user'])
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

        return DB::transaction(function () use ($id, $data, $item, $user) {
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
                'brand' => 'required',
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

                //take brand id from brands table
                $brand = $this->getBrandsHandler()->getOrCreateBrand($data['brand']);
                $item->brand_id = $brand->id;

                $item->save();
                $item = $item->fresh();

                //set active ingredients
                $activeIngredientIds = $this->getActiveIngredientsHandler()->getOrCreateActiveIngredients($data['active_ingredients']);
                $item->activeIngredients()->sync($activeIngredientIds);

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

    public function getReviews($itemSlug, $data)
    {
        try {
            $totalCount = 0;
            $rating = 0;

            $reviewsQ = Review::with(['user'])
                ->whereHas('item', function ($q) use ($itemSlug) {
                    $q->where('slug', $itemSlug);
                });

            $totalRatings = $reviewsQ->sum('rating');
            $totalCount = $reviewsQ->count();

            if (isset($data['page']) && isset($data['per_page'])) {
                $reviewsQ = $reviewsQ->skip(($data['page'] - 1) * $data['per_page'])
                    ->take($data['per_page']);
            }

            $reviews = $reviewsQ->orderBy('created_at', 'desc')->get();
            if ($totalRatings > 0) {
                $rating = $totalRatings / $totalCount;
            }

            return [
                'reviews' => $reviews,
                'total' => $totalCount,
                'rating' => $rating
            ];
        } catch (ModelNotFoundException $th) {
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
                $mainImage->delete();
                Storage::delete("public/" . $mainImage->location);
            }

            $fileData = [
                'item_id' => $item->id,
                'name' => $data['image_name'],
                'location' => "images/items/" . Carbon::now()->timestamp . $user->id,
                'image_data' => $data['image'],
                'is_default' => true,
            ];
            $file = $this->getFilesHandler()->create($fileData);

            $imageIds[] = $file->id;
        }

        //delete sub images
        if (isset($data['deleted_sub_images']) && $action == "update") {
            $files = $item->files->toArray();
            foreach ($data['deleted_sub_images'] as $key => $deletedImageId) {
                $fileIndex = array_search($deletedImageId, array_column($files, "id"));
                if ($fileIndex) {
                    $file = $files[$fileIndex];
                    File::where('id', $deletedImageId)->delete();
                    Storage::delete("public/" . $file['location']);
                }
            }
        }

        //upload sub images
        if (isset($data['sub_images'])) {
            foreach ($data['sub_images'] as $key => $image) {
                $fileData = [
                    'item_id' => $item->id,
                    'name' => $image['name'],
                    'location' => "images/items/sub_images/" . Carbon::now()->timestamp . $user->id . $key,
                    'image_data' => $image['data'],
                    'is_default' => false,

                ];
                $file = $this->getFilesHandler()->create($fileData);
                $imageIds[] = $file->id;
            }
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

    private function getActiveIngredientsHandler(): ActiveIngredientsHandler
    {
        return app(ActiveIngredientsHandler::class);
    }

    private function getBrandsHandler(): BrandsHandler
    {
        return app(BrandsHandler::class);
    }
}
