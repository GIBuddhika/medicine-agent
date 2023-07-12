<?php

namespace App\Http\Handlers;

use App\Constants\OrderStatusConstants;
use App\Constants\SessionConstants;
use App\Constants\UserRoleConstants;
use App\Models\ItemOrder;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ItemOrderHandler
{
    public function create(Order $order, $orderItem, $price): ItemOrder
    {
        $itemOrder = new ItemOrder();
        $itemOrder->item_id = $orderItem['item_id'];
        $itemOrder->order_id = $order->id;
        $itemOrder->quantity = $orderItem['quantity'];
        $itemOrder->price = $price;
        $itemOrder->duration = $orderItem['duration'];
        $itemOrder->status = OrderStatusConstants::PENDING;

        $itemOrder->save();
        return $itemOrder;
    }

    public function handleUpdate($orderId, $orderItemId, $data): ItemOrder
    {
        $itemOrder = ItemOrder::where('order_id', $orderId)
            ->where('item_id', $orderItemId)
            ->first();

        return $this->updateOrderItem($itemOrder, $data);
    }

    public function updateOrderItem(ItemOrder $itemOrder, $data): ItemOrder
    {
        if (isset($data['status'])) {
            $itemOrder->status = $data['status'];
        }
        if (isset($data['admin_note'])) {
            $itemOrder->admin_note = $data['admin_note'];
        }
        if (isset($data['collected_at'])) {
            $itemOrder->collected_at = $data['collected_at'];
        }

        $itemOrder->save();
        return $itemOrder;
    }

    public function markAsCollected($itemOrderId, $data)
    {
        try {
            $user = session(SessionConstants::User);
            $userRole = session(SessionConstants::UserRole);

            $itemOrderQ = ItemOrder::with('item.shop.shopAdmins', 'order')
                ->where('id', $itemOrderId);

            if ($userRole == UserRoleConstants::SHOP_ADMIN) {
                //checking ShopAdmin has access to the shop
                $itemOrderQ->whereHas('item', function ($query1) use ($user) {
                    $query1->whereHas('shop', function ($query2) use ($user) {
                        $query2->whereHas('shopAdmins', function ($query3) use ($user) {
                            $query3->where('user_id', $user->id);
                        });
                    });
                });
            } else {
                $itemOrderQ->whereHas('item', function ($query1) use ($user) {
                    $query1->where('user_id', $user->id);
                });
            }

            $itemOrder = $itemOrderQ->firstOrFail();

            $itemOrderData = [];
            $itemOrderData['status'] = OrderStatusConstants::COLLECTED;
            $itemOrderData['collected_at'] = Carbon::now();

            if (isset($data['admin_note'])) {
                $itemOrderData['admin_note'] = $data['admin_note'];
            }

            return $this->updateOrderItem($itemOrder, $itemOrderData);
        } catch (ModelNotFoundException $ex) {
            throw new ModelNotFoundException();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
