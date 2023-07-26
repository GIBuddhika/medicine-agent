<?php

namespace App\Http\Handlers;

use App\Constants\OrderStatusConstants;
use App\Constants\SessionConstants;
use App\Constants\UserRoleConstants;
use App\Constants\ValidationMessageConstants;
use App\Models\Item;
use App\Models\ItemOrder;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use App\PaymentService\PaymentService;
use App\Rules\IsQuantityAvailable;
use App\Rules\RequiredIfARentableItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

                //create payments records
                //this will create payment record for each order item with the respective paid amount for each order item 
                //and same invoice id will be set if there're multiple items in the cart.
                $this->createPaymentRecords($order, $user, $invoiceId);

                //update order_items
                $this->updateOrderItems($order);

                // update inventory                
                $this->getItemsHandler()->updateItemsCountAfterSuccessfulCheckout($order);

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
            $price = $this->getItemsHandler()->getPriceBasedOnQuantity($orderItem['item_id'], $orderItem['quantity']);
            $this->getItemOrderHandler()->create($order, $orderItem, $price);
        }
    }

    public function updateOrderItems(Order $order): void
    {
        $orderItemsData = $order->items;
        foreach ($orderItemsData as $orderItem) {
            $data = [
                'status' => OrderStatusConstants::SUCCESS
            ];
            $this->getItemOrderHandler()->handleUpdate($order->id, $orderItem['id'], $data);
        }
    }

    public function createPaymentRecords(Order $order, User $user, $invoiceId)
    {
        $orderItemsData = $order->items;
        foreach ($orderItemsData as $orderItem) {
            $this->createPaymentRecord($order, $orderItem, $user, $invoiceId, "cart-checkout");
        }
    }

    public function createPaymentRecord(Order $order, Item $orderItem, User $user, $invoiceId, $checkoutPoint, $duration = null)
    {
        $paid_amount = (int)$orderItem->pivot->quantity * (int)$orderItem->pivot->price;
        if ($orderItem->pivot->duration) {
            $paid_amount *= $orderItem->pivot->duration;
        }

        if ($checkoutPoint == "cart-checkout") {
            $log = $user->name . ' paid ' . $paid_amount . 'LKR on ' . Carbon::now()->format('Y M d h.ia') . ' at checkout.'; //or shop
        } else if ($checkoutPoint == "extend") {
            $log = $user->name . ' paid ' . $paid_amount
                . 'LKR on ' . Carbon::now()->format('Y M d h.ia')
                . ' to extend ' . $orderItem->name
                . ' by ' . $duration . ' months.'; //or shop
        }

        $paymentData = [
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'item_order_id' => $orderItem->pivot->id,
            'payment_type' => 'online', //after create pay on shop feature, change this.
            'payment_amount' => $paid_amount,
            'duration' => $duration ?? ($orderItem->pivot->duration ?? null),
            'online_payment_id' => $invoiceId,
            'log' => $log
        ];

        $this->getPaymentsHandler()->create($paymentData);
    }

    public function extend($orderData)
    {
        $user = session(SessionConstants::User);
        try {
            $order = Order::where('id', $orderData['order_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $orderItem = $order->items()
                ->where('item_id', $orderData['item_id'])
                ->firstOrFail();

            $this->validateOrderExtendData($orderData);

            $order = DB::transaction(function () use ($orderData, $user, $order, $orderItem) {
                //pay order
                $invoiceId = $this->getPaymentService()->processPayment($order->id, $user, $orderData['stripe_token']);

                //create payments records
                $this->createPaymentRecord($order, $orderItem,  $user, $invoiceId, "extend", $orderData['duration']);

                //update order_item duration
                $this->getItemOrderHandler()->handleUpdate($order->id, $orderItem['id'], [
                    'duration' => $orderData['duration'] + $orderItem->pivot->duration
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

    public function cancelOrderItem($orderId, $orderData)
    {
        $user = session(SessionConstants::User);

        //if userrole == customer, user $user session obj
        //else if it's admin or shop admin, take user_id from order and check order's item is belongs to that admin or shopadmin.

        try {
            $orderItem = ItemOrder::with(['payments', 'order.user'])
                ->where('id', $orderData['order_item_id'])
                ->where('order_id', $orderId)
                ->whereHas('order', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->firstOrFail();

            $orderItem = DB::transaction(function () use ($orderData, $orderItem) {

                //since orderitem is not-collected, it should only have one payment.
                $payment = $orderItem->payments[0];

                if ($payment['payment_type'] == "online") {
                    //refund stripe
                    $refund = $this->getPaymentService()->refund($payment, $payment->payment_amount);

                    //create refund record along with payment_id, so we know from which payment we made the refund
                    $this->createRefund($orderItem, $payment, $orderData, $refund);
                }

                //update the statuses
                $orderItem->status = OrderStatusConstants::CANCELLED;
                $orderItem->cancelled_at = Carbon::now();
                $orderItem->save();

                //increase items quantity
                $item = $orderItem->item;
                $item->quantity += $orderItem->quantity;
                $item->save();

                return $orderItem;
            });

            return $orderItem;
        } catch (ModelNotFoundException $ex) {
            throw new ModelNotFoundException();
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

    private function createRefund($orderItem, $payment, $orderData, $refund)
    {
        $refundData = [
            'order_id' => $orderItem->order_id,
            'item_order_id' => $orderItem->id,
            'user_id' => $orderItem->order->user_id,
            'payment_id' => $payment->id,
            'refund_type' => "online",
            'refund_amount' => $payment->payment_amount,
            'online_refund_id' => $refund['id'],
            'reason' => null
        ];

        if (isset($orderData['reason'])) {
            $refundData['reason'] = $orderData['reason'];
        }

        $customer = $orderItem->order->user;

        $log = $customer->name . ' has cancelled order at ' . Carbon::now()->format('Y M d h.ia') . '.'
            . ' Refunded amount: ' . $payment->payment_amount . 'LKR.';

        $refundData['log'] = $log;

        $this->getRefundsHandler()->create($refundData);
    }


    //for customer portal
    public function getUnCollectedOrderItems()
    {
        $user = session(SessionConstants::User);

        $unColletedShopOrderItems = $this->getShopOrderItems($user->id, OrderStatusConstants::SUCCESS);
        $unCollectedPersonalOrderItems = $this->getPersonalOrderItems($user->id, OrderStatusConstants::SUCCESS);

        $shops = Shop::whereIn('id', $unColletedShopOrderItems->keys())->get();
        $users = User::whereIn('id', $unCollectedPersonalOrderItems->keys())->get();

        return [
            'shopOrderItems' => $unColletedShopOrderItems,
            'personalOrderItems' => $unCollectedPersonalOrderItems,
            'shops' => $shops,
            'users' => $users
        ];
    }

    //for customer portal
    public function getCollectedOrderItems()
    {
        $user = session(SessionConstants::User);

        $colletedShopOrderItems = $this->getShopOrderItems($user->id, OrderStatusConstants::COLLECTED);
        $collectedPersonalOrderItems = $this->getPersonalOrderItems($user->id, OrderStatusConstants::COLLECTED);

        $shops = Shop::whereIn('id', $colletedShopOrderItems->keys())->get();
        $users = User::whereIn('id', $collectedPersonalOrderItems->keys())->get();

        return [
            'shopOrderItems' => $colletedShopOrderItems,
            'personalOrderItems' => $collectedPersonalOrderItems,
            'shops' => $shops,
            'users' => $users
        ];
    }

    //for customer portal
    public function getCancelledOrderItems()
    {
        $user = session(SessionConstants::User);

        $colletedShopOrderItems = $this->getShopOrderItems($user->id, OrderStatusConstants::CANCELLED);
        $collectedPersonalOrderItems = $this->getPersonalOrderItems($user->id, OrderStatusConstants::CANCELLED);

        $shops = Shop::whereIn('id', $colletedShopOrderItems->keys())->get();
        $users = User::whereIn('id', $collectedPersonalOrderItems->keys())->get();

        return [
            'shopOrderItems' => $colletedShopOrderItems,
            'personalOrderItems' => $collectedPersonalOrderItems,
            'shops' => $shops,
            'users' => $users
        ];
    }

    //for customer portal
    private function getShopOrderItems($userId, $status)
    {
        $shopOrderItems = DB::select(
            DB::raw(
                "SELECT 
                item_order.*,
                orders.created_at as order_created_at,
                orders.user_id as user_id,
                items.id as item_id,
                items.shop_id,
                items.name,
                items.image_id,
                files.location,
                payments.payment_type
                FROM `item_order` 
                join orders on item_order.order_id=orders.id
                join items on item_order.item_id=items.id
                join files on files.id=items.image_id
                join payments on payments.item_order_id=item_order.id
                where orders.user_id=$userId AND items.shop_id is not null
                AND item_order.status = " . $status . "
                order By order_created_at DESC
            "
            )
        );

        $colletedShopOrderItems = ItemOrder::hydrate($shopOrderItems)
            ->groupBy('shop_id');

        return $colletedShopOrderItems;
    }

    //for customer portal
    private function getPersonalOrderItems($userId, $status)
    {
        $personalOrderItems = DB::select(
            DB::raw(
                "SELECT 
                item_order.*,
                orders.created_at as order_created_at,
                orders.user_id as user_id,
                items.id as item_id,
                items.shop_id,
                items.name,
                items.image_id,
                personal_listings.user_id as personal_listing_user_id,
                personal_listings.address,
                personal_listings.latitude,
                personal_listings.longitude,
                files.location,
                payments.payment_type
                FROM `item_order` 
                join orders on item_order.order_id=orders.id
                join items on item_order.item_id=items.id
                join personal_listings on items.personal_listing_id=personal_listings.id
                join files on files.id=items.image_id
                join payments on payments.item_order_id=item_order.id
                where item_order.status=" . $status . "  AND orders.user_id=$userId and items.shop_id is null 
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
            'data.*.quantity' => ['required', 'integer', new IsQuantityAvailable()],
            'data.*.duration' => [new RequiredIfARentableItem(), 'present'],
        ];

        $messages = [
            'required' => ValidationMessageConstants::Required,
            'integer' => ValidationMessageConstants::IntegerValue,
            'exists' => ValidationMessageConstants::NotFound,
            'required_with' => ValidationMessageConstants::Required,
            'required_without' => ValidationMessageConstants::Required,
            'required_if' => ValidationMessageConstants::Required,
        ];

        $validator = Validator::make($orderData, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator, 400);
        }
    }

    private function validateOrderExtendData(array $orderData)
    {
        $rules = [
            'stripe_token' => 'required',
            'order_id' => 'required|exists:orders,id',
            'item_id' => 'required|exists:items,id',
            'duration' => ['present', 'integer'],
        ];

        $messages = [
            'required' => ValidationMessageConstants::Required,
            'exists' => ValidationMessageConstants::NotFound,
            'integer' => ValidationMessageConstants::IntegerValue,
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

    //for admin portal
    public function getShopOrderItemsForAdmin(array $data)
    {
        $rules = [
            'status' => ['required', Rule::in([
                OrderStatusConstants::SUCCESS,
                OrderStatusConstants::COLLECTED,
            ])],
        ];

        $messages = [
            'required' => ValidationMessageConstants::Required,
            'in' => ValidationMessageConstants::Invalid,
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator, 400);
        }

        $user = session(SessionConstants::User);
        $userRole = session(SessionConstants::UserRole);

        $shopIds = [];

        if ($userRole == UserRoleConstants::ADMIN) {
            $userId = $user->id;
            if (isset($data['shop_id'])) {
                $shopIds[] = $data['shop_id'];
            }
        } else if ($userRole == UserRoleConstants::SHOP_ADMIN) {
            $userId = $user->owner_id;
            $assignedShops = $user->shops;
            if (isset($data['shop_id'])) {
                $shopId = $data['shop_id'];
                $shopIndex = collect($assignedShops)->search(function ($shop) use ($shopId) {
                    return $shop->id == $shopId;
                });
                if ($shopIndex !== false) {
                    $shopIds[] = $shopId;
                } else {
                    throw new ModelNotFoundException();
                }
            } else {
                $shopIds = $assignedShops->pluck('id')->toArray();
            }
        }

        $shopIdsString = implode(",", $shopIds);

        $status = $data['status'];

        $orderId = null;
        if (isset($data['order_id'])) {
            $orderId = $data['order_id'];
        }
        $itemId = null;
        if (isset($data['product_id'])) {
            $itemId = $data['product_id'];
        }
        $date = null;
        if (isset($data['date'])) {
            $date = $data['date'];
        }
        $phone = null;
        if (isset($data['phone'])) {
            $phone = $data['phone'];
        }

        $unColletedShopOrderItems = $this->getShopOrderItemsAdminFromDB($status, $shopIdsString, $userId, $orderId, $itemId, $date, $phone);
        $users = User::whereIn('id', $unColletedShopOrderItems->keys())->get();
        return [
            'order_items' => $unColletedShopOrderItems,
            'users' => $users
        ];
    }

    //for admin portal    
    private function getShopOrderItemsAdminFromDB($status, $shopIdsString = "", $userId, $orderId, $itemId, $date, $phone)
    {
        $rawQuery = "SELECT 
            item_order.*,
            orders.created_at as order_created_at,
            orders.user_id as order_user_id,
            items.id as item_id,
            items.shop_id,
            items.image_id,
            items.name,
            files.location
            FROM `item_order` 
            join orders on item_order.order_id=orders.id
            join items on item_order.item_id=items.id
            join files on files.id=items.image_id
            join users on users.id=orders.user_id
            where items.user_id = $userId
            AND item_order.status = " . (($status == OrderStatusConstants::SUCCESS) ? OrderStatusConstants::SUCCESS : OrderStatusConstants::COLLECTED) . "
            " . (($itemId != null) ? "AND item_order.item_id = $itemId " : "") . "
            " . (($phone != null) ? "AND users.phone LIKE '%$phone%' " : "") . "
            " . (($date != null) ? "AND orders.created_at LIKE '$date%' " : "") . "
            " . (($orderId != null) ? "AND orders.id = $orderId " : "") . "
            " . (($shopIdsString != "") ? "AND items.shop_id IN ($shopIdsString) " : "AND items.shop_id is not null ") . "
            order By order_created_at DESC";

        $shopOrderItems = DB::select(
            DB::raw($rawQuery)
        );

        $unColletedShopOrderItems = ItemOrder::hydrate($shopOrderItems)
            ->groupBy('order_user_id');

        return $unColletedShopOrderItems;
    }

    //for admin portal
    public function getPersonalOrderItemsForAdmin(array $data)
    {
        $rules = [
            'status' => ['required', Rule::in([
                OrderStatusConstants::SUCCESS,
                OrderStatusConstants::COLLECTED,
            ])],
        ];

        $messages = [
            'required' => ValidationMessageConstants::Required,
            'in' => ValidationMessageConstants::Invalid,
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator, 400);
        }

        $user = session(SessionConstants::User);
        $userRole = session(SessionConstants::UserRole);

        $status = $data['status'];

        $orderId = null;
        if (isset($data['order_id'])) {
            $orderId = $data['order_id'];
        }
        $itemId = null;
        if (isset($data['product_id'])) {
            $itemId = $data['product_id'];
        }
        $date = null;
        if (isset($data['date'])) {
            $date = $data['date'];
        }
        $phone = null;
        if (isset($data['phone'])) {
            $phone = $data['phone'];
        }

        $unColletedShopOrderItems = $this->getPersonalOrderItemsAdminFromDB($status, $user->id, $orderId, $itemId, $date, $phone);
        $users = User::whereIn('id', $unColletedShopOrderItems->keys())->get();
        return [
            'order_items' => $unColletedShopOrderItems,
            'users' => $users
        ];
    }

    //for admin portal    
    private function getPersonalOrderItemsAdminFromDB($status, $userId, $orderId, $itemId, $date, $phone)
    {
        $rawQuery = "SELECT 
            item_order.*,
            orders.created_at as order_created_at,
            orders.user_id as order_user_id,
            items.id as item_id,
            items.personal_listing_id,
            items.image_id,
            items.name,
            files.location
            FROM `item_order` 
            join orders on item_order.order_id=orders.id
            join items on item_order.item_id=items.id
            join files on files.id=items.image_id
            join users on users.id=orders.user_id
            where items.user_id = $userId
            AND item_order.status = " . (($status == OrderStatusConstants::SUCCESS) ? OrderStatusConstants::SUCCESS : OrderStatusConstants::COLLECTED) . "
            " . (($itemId != null) ? "AND item_order.item_id = $itemId " : "") . "
            " . (($phone != null) ? "AND users.phone LIKE '%$phone%' " : "") . "
            " . (($date != null) ? "AND orders.created_at LIKE '$date%' " : "") . "
            " . (($orderId != null) ? "AND orders.id = $orderId " : "") . "
            AND items.personal_listing_id is not null
            order By order_created_at DESC";

        $shopOrderItems = DB::select(
            DB::raw($rawQuery)
        );

        $unColletedShopOrderItems = ItemOrder::hydrate($shopOrderItems)
            ->groupBy('order_user_id');

        return $unColletedShopOrderItems;
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

    private function getPaymentsHandler(): PaymentsHandler
    {
        return app(PaymentsHandler::class);
    }

    private function getRefundsHandler(): RefundsHandler
    {
        return app(RefundsHandler::class);
    }
}
