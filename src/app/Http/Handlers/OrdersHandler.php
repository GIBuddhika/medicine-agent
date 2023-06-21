<?php

namespace App\Http\Handlers;

use App\Constants\OrderStatusConstants;
use App\Constants\SessionConstants;
use App\Constants\ValidationMessageConstants;
use App\Models\ItemOrder;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
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
        $order->status = OrderStatusConstants::PENDING;
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

    public function getUnCollectedOrderItems()
    {
        $user = session(SessionConstants::User);

        $unColletedShopOrderItems = $this->getUnCollectedShopOrderItems($user->id);
        $unCollectedPersonalOrderItems = $this->getUnCollectedPersonalOrderItems($user->id);

        $shops = Shop::whereIn('id', $unColletedShopOrderItems->keys())->get();
        $users = User::whereIn('id', $unCollectedPersonalOrderItems->keys())->get();

        return [
            'unColletedShopOrderItems' => $unColletedShopOrderItems,
            'unCollectedPersonalOrderItems' => $unCollectedPersonalOrderItems,
            'shops' => $shops,
            'users' => $users
        ];
    }

    public function getCollectedOrderItems()
    {
        $user = session(SessionConstants::User);

        $colletedShopOrderItems = $this->getCollectedShopOrderItems($user->id);
        $collectedPersonalOrderItems = $this->getCollectedPersonalOrderItems($user->id);

        $shops = Shop::whereIn('id', $colletedShopOrderItems->keys())->get();
        $users = User::whereIn('id', $collectedPersonalOrderItems->keys())->get();

        return [
            'colletedShopOrderItems' => $colletedShopOrderItems,
            'collectedPersonalOrderItems' => $collectedPersonalOrderItems,
            'shops' => $shops,
            'users' => $users
        ];
    }

    private function getUnCollectedShopOrderItems($userId)
    {
        $shopOrderItems = DB::select(
            DB::raw(
                "SELECT 
                item_order.*,
                orders.created_at as order_created_at,
                orders.user_id as user_id,
                items.id as item_id,
                orders.status as status,
                items.shop_id,
                items.image_id,
                files.location
                FROM `item_order` 
                join orders on item_order.order_id=orders.id
                join items on item_order.item_id=items.id
                join files on files.id=items.image_id
                where status=" . OrderStatusConstants::SUCCESS . " AND orders.user_id=$userId and items.shop_id is not null 
                order By order_created_at DESC
            "
            )
        );

        $unColletedShopOrderItems = ItemOrder::hydrate($shopOrderItems)
            ->groupBy('shop_id');

        return $unColletedShopOrderItems;
    }

    private function getUnCollectedPersonalOrderItems($userId)
    {
        $personalOrderItems = DB::select(
            DB::raw(
                "SELECT 
                item_order.*,
                orders.created_at as order_created_at,
                orders.user_id as user_id,
                items.id as item_id,
                orders.status as status,
                items.shop_id,
                personal_listings.user_id as personal_listing_user_id,
                personal_listings.address,
                personal_listings.phone,
                personal_listings.latitude,
                personal_listings.longitude,
                items.image_id,
                files.location
                FROM `item_order` 
                join orders on item_order.order_id=orders.id
                join items on item_order.item_id=items.id
                join personal_listings on items.personal_listing_id=personal_listings.id
                join files on files.id=items.image_id
                where status=" . OrderStatusConstants::SUCCESS . "  AND orders.user_id=$userId and items.shop_id is null 
                order By order_created_at DESC
            "
            )
        );

        $uncollectedPersonalProducts = ItemOrder::hydrate($personalOrderItems)
            ->groupBy('personal_listing_user_id');

        return $uncollectedPersonalProducts;
    }

    private function getCollectedShopOrderItems($userId)
    {
        $shopOrderItems = DB::select(
            DB::raw(
                "SELECT 
                item_order.*,
                orders.created_at as order_created_at,
                orders.user_id as user_id,
                items.id as item_id,
                orders.status as status,
                items.shop_id,
                items.image_id,
                files.location
                FROM `item_order` 
                join orders on item_order.order_id=orders.id
                join items on item_order.item_id=items.id
                join files on files.id=items.image_id
                where status=" . OrderStatusConstants::COLLECTED . " AND orders.user_id=$userId and items.shop_id is not null 
                order By order_created_at DESC
            "
            )
        );

        $colletedShopOrderItems = ItemOrder::hydrate($shopOrderItems)
            ->groupBy('shop_id');

        return $colletedShopOrderItems;
    }


    private function getCollectedPersonalOrderItems($userId)
    {
        $personalOrderItems = DB::select(
            DB::raw(
                "SELECT 
                item_order.*,
                orders.created_at as order_created_at,
                orders.user_id as user_id,
                items.id as item_id,
                orders.status as status,
                items.shop_id,
                personal_listings.user_id as personal_listing_user_id,
                personal_listings.address,
                personal_listings.phone,
                personal_listings.latitude,
                personal_listings.longitude,
                items.image_id,
                files.location
                FROM `item_order` 
                join orders on item_order.order_id=orders.id
                join items on item_order.item_id=items.id
                join personal_listings on items.personal_listing_id=personal_listings.id
                join files on files.id=items.image_id
                where status=" . OrderStatusConstants::COLLECTED . "  AND orders.user_id=$userId and items.shop_id is null 
                order By order_created_at DESC
            "
            )
        );

        $collectedPersonalProducts = ItemOrder::hydrate($personalOrderItems)
            ->groupBy('personal_listing_user_id');

        return $collectedPersonalProducts;
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
