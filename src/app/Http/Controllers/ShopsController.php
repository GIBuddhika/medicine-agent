<?php

namespace App\Http\Controllers;

use App\Http\Handlers\ShopsHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShopsController extends Controller
{
    public function all()
    {
        try {
            $shops = $this
                ->getShopsHandler()
                ->getAll();

            return $shops;
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        }
    }

    public function one(int $id)
    {
        try {
            $shop = $this
                ->getShopsHandler()
                ->getShop($id);

            return $shop;
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        }
    }

    public function delete(int $id)
    {
        try {
            $this
                ->getShopsHandler()
                ->deleteShop($id);

            return response([], 200);
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        }
    }

    public function create(Request $request)
    {
        try {
            $shop = $this
                ->getShopsHandler()
                ->createShop($request->toArray());

            return $shop;
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $shop = $this
                ->getShopsHandler()
                ->updateShop($id, $request->toArray());

            return response($shop, 201);
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        }
    }

    public function geItems(Request $request, int $id)
    {
        try {
            $shop = $this
                ->getShopsHandler()
                ->geItems($id, $request->toArray());

            return response($shop, 201);
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        }
    }

    private function getShopsHandler(): ShopsHandler
    {
        return app(ShopsHandler::class);
    }
}
