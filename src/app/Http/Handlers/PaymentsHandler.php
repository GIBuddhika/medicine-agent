<?php

namespace App\Http\Handlers;

use App\Models\Payment;

class PaymentsHandler
{
    public function create($data): Payment
    {
        $payment = new Payment();
        $payment->user_id = $data['user_id'];
        $payment->order_id = $data['order_id'];
        $payment->item_order_id = $data['item_order_id'];
        $payment->payment_type = $data['payment_type'];
        $payment->payment_amount = $data['payment_amount'];
        $payment->duration = $data['duration'];
        $payment->online_payment_id = $data['online_payment_id'];
        $payment->log = $data['log'];

        $payment->save();

        return $payment;
    }
}
