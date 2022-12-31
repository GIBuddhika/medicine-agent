<?php

namespace App\Http\Controllers;

use App\Http\Handlers\ItemsHandler;
use App\Http\Handlers\OrdersHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrdersController extends Controller
{

    public function create(Request $request)
    {
        try {
            $item = $this
                ->getOrdersHandler()
                ->create($request->toArray());

            return $item;
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        }
    }
    private function getOrdersHandler(): OrdersHandler
    {
        return app(OrdersHandler::class);
    }
}
