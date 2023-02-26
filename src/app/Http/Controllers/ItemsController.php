<?php

namespace App\Http\Controllers;

use App\Http\Handlers\ItemsHandler;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ItemsController extends Controller
{

    public function create(Request $request)
    {
        try {
            $item = $this
                ->getItemsHandler()
                ->createItem($request->toArray());

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
            $items = $this
                ->getItemsHandler()
                ->getAll($request->toArray());

            return response()->json($items['data'])
                ->header('App-Content-Full-Count', $items['total']);
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        }
    }

    public function get(Request $request, String $slug)
    {
        try {
            $item = $this
                ->getItemsHandler()
                ->get($slug);

            return response()->json($item);
        } catch (NotFoundHttpException $ex) {
            return response(null, 404);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $item = $this
                ->getItemsHandler()
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
                ->getItemsHandler()
                ->deleteItem($id, $request->toArray());

            return [];
        } catch (NotFoundHttpException $ex) {
            return response(null, 404);
        }
    }

    private function getItemsHandler(): ItemsHandler
    {
        return app(ItemsHandler::class);
    }
}
