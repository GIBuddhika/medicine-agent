<?php

namespace App\Http\Handlers;

use App\Constants\OrderStatusConstants;
use App\Constants\SessionConstants;
use App\Constants\ValidationMessageConstants;
use App\ItemOrder;
use App\Models\Order;
use App\PaymentService\PaymentService;
use App\Rules\RequiredIfARentableItem;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\Exception\OAuth\InvalidRequestException;

class OrdersHandler
{
    public function handleCreateOrderRequest(array $orderData)
    {
        $user = session(SessionConstants::User);
        try {
            $this->validateOrderData($orderData);

            $order = DB::transaction(function () use ($orderData, $user) {
                //create order
                $order = $this->createOrder($user);

                //create order_items
                $this->createOrderItems($order, $orderData['data']);

                //pay order
                $invoiceId = $this->getPaymentService()->processPayment($order->id, $user, $orderData['stripe_token']);

                //update order table data
                $order = $this->updateOrder($order->id, [
                    'stripe_invoice_id' => $invoiceId,
                    'status' => OrderStatusConstants::SUCCESS
                ]);

                return $order;
            });

            return $order;
        } catch (ValidationException $ex) {
            throw new ValidationException($ex);
        } catch (CardException $ex) {
            throw new CardException($ex);
        } catch (InvalidRequestException $ex) {
            throw new InvalidRequestException($ex);
        } catch (Exception $ex) {
            throw new Exception($ex);
        }
    }

    public function createOrder($user)
    {
        $order = new Order();
        $order->user_id = $user->id;
        $order->status = OrderStatusConstants::NEW;
        $order->save();
        return $order;
    }

    public function updateOrder($orderId, $data)
    {
        $order = Order::where('id', $orderId)->first();

        if (isset($data['stripe_invoice_id'])) {
            $order->stripe_invoice_id = $data['stripe_invoice_id'];
        }
        if (isset($data['status'])) {
            $order->status = $data['status'];
        }

        $order->save();
        return $order;
    }

    public function createOrderItems(Order $order,  array $orderItemsData): void
    {
        foreach ($orderItemsData as $orderItem) {
            $price = $this->getItemsHandler()->getPriceByQuantity($orderItem['item_id'], $orderItem['quantity']);
            $this->getItemOrderHandler()->create($order, $orderItem, $price);
        }
    }

    private function validateOrderData(array $orderData)
    {
        $rules = [
            'stripe_token' => 'required',
            'data.*.item_id' => 'required|exists:items,id',
            'data.*.quantity' => 'required|integer',
            'data.*.duration' => [new RequiredIfARentableItem(), 'present'],
        ];

        $messages = [
            'required' => ValidationMessageConstants::Required,
            'integer' => ValidationMessageConstants::IntegerValue,
            'exists' => ValidationMessageConstants::NotFound,
            'numeric' => ValidationMessageConstants::Invalid,
            'base64' => ValidationMessageConstants::Invalid,
            'required_with' => ValidationMessageConstants::Required,
            'required_without' => ValidationMessageConstants::Required,
            'required_if' => ValidationMessageConstants::Required,
        ];

        $validator = Validator::make($orderData, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator, 400);
        }
    }

    public function getTotal(int $orderId)
    {
        $total = 0;
        $items = Order::find($orderId)->items;

        foreach ($items as $item) {
            $singleItemPrice = $item->pivot->price * $item->pivot->quantity;
            if ($item->pivot->duration) { //rentable item
                $singleItemPrice *= $item->pivot->duration;
            }
            $total += $singleItemPrice;
        }

        return $total;
    }

    private function getPaymentService(): PaymentService
    {
        return app(PaymentService::class);
    }

    private function getItemsHandler(): ItemsHandler
    {
        return app(ItemsHandler::class);
    }

    private function getItemOrderHandler(): ItemOrderHandler
    {
        return app(ItemOrderHandler::class);
    }
}
