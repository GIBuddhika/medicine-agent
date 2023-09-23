<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Handlers\ActiveIngredientsHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ActiveIngredientsController extends Controller
{
    public function getNames(Request $request)
    {
        try {
            $activeIngredients = $this
                ->getActiveIngredientsHandler()
                ->getNames($request->toArray());

            return $activeIngredients;
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        }
    }

    private function getActiveIngredientsHandler(): ActiveIngredientsHandler
    {
        return app(ActiveIngredientsHandler::class);
    }
}
