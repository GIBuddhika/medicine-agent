<?php

namespace App\Http\Controllers;

use App\Http\Handlers\UsersHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

    private function getUsersHandler(): UsersHandler
    {
        return app(UsersHandler::class);
    }

}
