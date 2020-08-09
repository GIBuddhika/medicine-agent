<?php

namespace App\Http\Controllers;

use App\Http\Handlers\ItemsHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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

    private function getItemsHandler(): ItemsHandler
    {
        return app(ItemsHandler::class);
    }
}
