<?php

namespace App\Http\Controllers;

use App\Http\Handlers\ItemOrderHandler;
use App\Http\Handlers\OrdersHandler;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\CardException;
use Stripe\Exception\OAuth\InvalidRequestException;

class OrdersController extends Controller
{

    public function create(Request $request)
    {
        try {
            $order = $this
                ->getOrdersHandler()
                ->handleCreateOrderRequest($request->toArray());

            return $order;
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        } catch (CardException $ex) {
            return response($ex->getMessage(), 422);
        } catch (InvalidRequestException $ex) {
            return response($ex->getMessage(), 422);
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public function getUnCollectedOrderItems(Request $request)
    {
        try {
            $orders = $this
                ->getOrdersHandler()
                ->getUnCollectedOrderItems($request->toArray());

            return $orders;
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public function getCollectedOrderItems(Request $request)
    {
        try {
            $orders = $this
                ->getOrdersHandler()
                ->getCollectedOrderItems($request->toArray());

            return $orders;
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public function getShopOrderItemsForAdmin(Request $request)
    {
        try {
            $orders = $this
                ->getOrdersHandler()
                ->getShopOrderItemsForAdmin($request->toArray());

            return $orders;
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public function getPersonalOrderItemsForAdmin(Request $request)
    {
        try {
            $orders = $this
                ->getOrdersHandler()
                ->getPersonalOrderItemsForAdmin($request->toArray());

            return $orders;
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    public function markItemOrderAsCollected(Request $request, $itemOrderId)
    {
        try {
            $orders = $this
                ->getItemOrderHandler()
                ->markAsCollected($itemOrderId, $request->toArray());

            return $orders;
        } catch (ValidationException $ex) {
            return response($ex->validator->errors(), 400);
        } catch (ModelNotFoundException $ex) {
            return response([], 404);
        } catch (Exception $ex) {
            return response($ex->getMessage(), 500);
        }
    }

    private function getOrdersHandler(): OrdersHandler
    {
        return app(OrdersHandler::class);
    }

    private function getItemOrderHandler(): ItemOrderHandler
    {
        return app(ItemOrderHandler::class);
    }
}
