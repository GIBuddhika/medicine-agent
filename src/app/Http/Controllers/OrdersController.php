<?php

namespace App\Http\Controllers;

use App\Http\Handlers\OrdersHandler;
use Exception;
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

    private function getOrdersHandler(): OrdersHandler
    {
        return app(OrdersHandler::class);
    }
}
