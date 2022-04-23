<?php

namespace App\Http\Controllers;

use App\Http\Handlers\DistrictsHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DistrictsController extends Controller
{
    public function all()
    {
        try {
            $districts = $this
                ->getDistrictsHandler()
                ->getAll();

            return $districts;
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        }
    }

    public function getCities(int $id)
    {
        try {
            $cities = $this
                ->getDistrictsHandler()
                ->getCities($id);

            return $cities;
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        }
    }

    private function getDistrictsHandler(): DistrictsHandler
    {
        return app(DistrictsHandler::class);
    }
}
