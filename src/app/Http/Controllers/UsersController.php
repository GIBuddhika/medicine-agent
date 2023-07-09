<?php

namespace App\Http\Controllers;

use App\Http\Handlers\UsersHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Svg\Tag\Rect;

class UsersController extends Controller
{
    public function get(int $id)
    {
        try {
            $user = $this
                ->getUsersHandler()
                ->get($id);

            return $user;
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        }
    }

    public function getShops(Request $request, int $id)
    {
        $data = [
            'is_a_personal_listing' => $request->is_a_personal_listing
        ];

        try {
            $shops = $this
                ->getUsersHandler()
                ->getShops($id, $data);

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
                ->getUsersHandler()
                ->getItems($id, $data);

            return response()->json($products['data'])
                ->header('App-Content-Full-Count', $products['total']);
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        }
    }

    public function getShopAdmins(int $id)
    {
        try {
            $shops = $this
                ->getUsersHandler()
                ->getShopAdmins($id);

            return $shops;
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        }
    }

    public function getPersonalItems(int $id)
    {
        try {
            $shops = $this
                ->getUsersHandler()
                ->getPersonalItems($id);

            return $shops;
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        }
    }

    private function getUsersHandler(): UsersHandler
    {
        return app(UsersHandler::class);
    }
}
