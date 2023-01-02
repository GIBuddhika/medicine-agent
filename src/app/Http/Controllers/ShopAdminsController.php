<?php

namespace App\Http\Controllers;

use App\Http\Handlers\ShopAdminsHandler;
use App\Http\Handlers\UsersHandler;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShopAdminsController extends Controller
{
    public function create(Request $request)
    {
        $data = [
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => $request->password,
            'password_confirmation' => $request->confirm_password,
            'shop_ids' => json_decode($request->shop_ids),
        ];

        try {
            $shopAdmin = $this
                ->getShopAdminsHandler()
                ->create($data);

            return $shopAdmin;
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        } catch (Exception $ex) {
            return response($ex, 500);
        }
    }

    public function update(Request $request, int $id)
    {
        $data = [
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => $request->password,
            'password_confirmation' => $request->confirm_password,
            'shop_ids' => json_decode($request->shop_ids),
        ];

        try {
            $shopAdmin = $this
                ->getShopAdminsHandler()
                ->update($id, $data);

            return $shopAdmin;
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        } catch (Exception $ex) {
            return response($ex, 500);
        }
    }

    public function get(int $id)
    {
        try {
            $shopAdmin = $this
                ->getShopAdminsHandler()
                ->get($id);

            return $shopAdmin;
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        }
    }

    public function delete(int $id)
    {
        try {
            $shopAdmin = $this
                ->getShopAdminsHandler()
                ->delete($id);

            return $shopAdmin;
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        }
    }

    public function all()
    {
        try {
            $shopAdmins = $this
                ->getShopAdminsHandler()
                ->all();

            return $shopAdmins;
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        }
    }

    public function getShops(int $id)
    {
        try {
            $shops = $this
                ->getShopAdminsHandler()
                ->getShops($id);

            return $shops;
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        }
    }

    public function getItems(Request $request, int $id)
    {
        try {
            $data = $request->toArray();
            $products = $this
                ->getShopAdminsHandler()
                ->getItems($id, $data);

            return response()->json($products['data'])
                ->header('App-Content-Full-Count', $products['total']);
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        }
    }

    private function getShopAdminsHandler(): ShopAdminsHandler
    {
        return app(ShopAdminsHandler::class);
    }
}
