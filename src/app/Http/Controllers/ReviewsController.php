<?php

namespace App\Http\Controllers;

use App\Http\Handlers\ReviewsHandler;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReviewsController extends Controller
{

    public function create(Request $request)
    {
        try {
            $item = $this
                ->getReviewsHandler()
                ->createReview($request->toArray());

            return $item;
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        } catch (Exception $ex) {
            return response([], 500);
        }
    }

    public function all(Request $request)
    {
        try {
            $Reviews = $this
                ->getReviewsHandler()
                ->getAll($request->toArray());

            return response()->json($Reviews['data'])
                ->header('App-Content-Full-Count', $Reviews['total']);
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $item = $this
                ->getReviewsHandler()
                ->updateItem($id, $request->toArray());

            return $item;
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        } catch (NotFoundHttpException $ex) {
            return response(null, 404);
        }
    }

    public function delete(Request $request, int $id)
    {
        try {
            $item = $this
                ->getReviewsHandler()
                ->deleteItem($id);

            return [];
        } catch (NotFoundHttpException $ex) {
            return response(null, 404);
        }
    }

    private function getReviewsHandler(): ReviewsHandler
    {
        return app(ReviewsHandler::class);
    }
}
