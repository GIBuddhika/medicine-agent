<?php

namespace App\Http\Handlers;

use App\Constants\OrderStatusConstants;
use App\Models\ItemOrder;
use App\Models\Order;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;

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

    public function update($orderId, $orderItemId, $data): ItemOrder
    {
        $itemOrder = ItemOrder::where('order_id', $orderId)
            ->where('item_id', $orderItemId)
            ->first();

        if (isset($data['status'])) {
            $itemOrder->status = $data['status'];
        }

        $itemOrder->save();
        return $itemOrder;
    }
}
