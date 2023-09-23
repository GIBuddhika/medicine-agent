<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Handlers\BrandsHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BrandsController extends Controller
{
    public function getNames(Request $request)
    {
        try {
            $activeIngredients = $this
                ->getBrandsHandler()
                ->getNames($request->toArray());

            return $activeIngredients;
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        }
    }

    private function getBrandsHandler(): BrandsHandler
    {
        return app(BrandsHandler::class);
    }
}
