<?php

namespace App\Http\Handlers;

use App\Constants\SessionConstants;
use App\Constants\ValidationMessageConstants;
use App\Models\ItemOrder;
use App\Models\Review;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReviewsHandler
{
    public function getAll($data)
    {
        $totalCount = 0;

        $reviewsQ = Review::query();

        if (isset($data['item_order_id'])) {
            $reviewsQ->where('item_order_id', $data['item_order_id']);
        }

        if (isset($data['item_id'])) {
            $reviewsQ->where('item_id', $data['item_id']);
        }

        if (isset($data['page']) && isset($data['per_page'])) {
            $totalCount = $reviewsQ->count();
            $reviewsQ = $reviewsQ->skip(($data['page'] - 1) * $data['per_page'])
                ->take($data['per_page']);
        }

        $reviews = $reviewsQ->orderBy('created_at', 'desc')->get();

        return [
            'data' => $reviews,
            'total' => $totalCount,
        ];
    }

    public function createReview($data)
    {
        $user = session(SessionConstants::User);
        $rules = [
            'item_order_id' => 'required|integer|exists:item_order,id',
            'rating' => 'required|integer',
        ];

        $messages = [
            'required' => ValidationMessageConstants::Required,
            'integer' => ValidationMessageConstants::IntegerValue,
        ];

        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator, 400);
        }

        $item = ItemOrder::with('order')
            ->where('id', $data['item_order_id'])
            ->whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->firstOrFail()
            ->item;

        try {
            $review = new Review();
            $review->user_id = $user->id;
            $review->item_id = $item->id;
            $review->item_order_id = $data['item_order_id'];
            $review->rating = $data['rating'];
            $review->comment = $data['comment'];

            $review->save();

            return $review->fresh();
        } catch (ModelNotFoundException $th) {
            throw $th;
        } catch (ValidationException $th) {
            throw new ValidationException($th, 400);
        } catch (Exception $th) {
            Log::info($th);
            throw $th;
        }
    }

    public function updateItem($id, $data)
    {
        $user = session(SessionConstants::User);
        $rules = [
            'item_order_id' => 'required|integer|exists:item_order,id',
            'rating' => 'integer',
        ];

        $messages = [
            'required' => ValidationMessageConstants::Required,
            'integer' => ValidationMessageConstants::IntegerValue,
        ];

        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator, 400);
        }

        $review = Review::with('itemOrder.order')
            ->where('id', $id)
            ->whereHas('itemOrder', function ($q) use ($user) {
                $q->whereHas('order', function ($q1) use ($user) {
                    $q1->where('user_id', $user->id);
                });
            })
            ->firstOrFail();

        try {
            if (isset($data['rating'])) {
                $review->rating = $data['rating'];
            }
            if (isset($data['comment'])) {
                $review->comment = $data['comment'];
            }

            $review->save();

            return $review->fresh();
        } catch (ModelNotFoundException $th) {
            throw $th;
        } catch (ValidationException $th) {
            throw new ValidationException($th, 400);
        } catch (Exception $th) {
            Log::info($th);
            throw $th;
        }
    }

    public function deleteItem($id)
    {
        try {
            $user = session(SessionConstants::User);

            $review = Review::with('itemOrder.order')
                ->where('id', $id)
                ->whereHas('itemOrder', function ($q) use ($user) {
                    $q->whereHas('order', function ($q1) use ($user) {
                        $q1->where('user_id', $user->id);
                    });
                })
                ->firstOrFail();

            $review->delete();
        } catch (\Throwable $th) {
            throw new NotFoundHttpException(404);
        }
    }
}
