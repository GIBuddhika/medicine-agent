<?php

namespace App\Http\Handlers;

use App\Models\Refund;

class RefundsHandler
{
    public function create($data): Refund
    {
        $refund = new Refund();
        $refund->order_id = $data['order_id'];
        $refund->item_order_id = $data['item_order_id'];
        $refund->user_id = $data['user_id'];
        $refund->payment_id = $data['payment_id'];
        $refund->refund_type = $data['refund_type'];
        $refund->refund_amount = $data['refund_amount'];
        $refund->online_refund_id = $data['online_refund_id'];
        $refund->reason = $data['reason'];
        $refund->log = $data['log'];

        $refund->save();

        return $refund;
    }
}
