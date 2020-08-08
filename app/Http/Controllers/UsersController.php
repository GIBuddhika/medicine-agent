<?php

namespace App\Http\Controllers;

use App\Http\Handlers\UsersHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function getShops(int $id)
    {
        try {
            $shops = $this
                ->getUsersHandler()
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
                ->getUsersHandler()
                ->getItems($id, $data);

            return response()->json($products['data'])
                ->header('App-Content-Full-Count', $products['total']);
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        }
    }

    private function getUsersHandler(): UsersHandler
    {
        return app(UsersHandler::class);
    }
}
