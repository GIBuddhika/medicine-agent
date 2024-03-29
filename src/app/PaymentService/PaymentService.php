<?php

namespace App\PaymentService;

use App\Http\Handlers\OrdersHandler;
use App\Models\User;

class PaymentService
{
    private $strategy;
    private $ordersHandler;

    public function __construct(
        OrdersHandler $ordersHandler,
        StripeStrategy $strategy
    ) {
        $this->strategy = $strategy;
        $this->ordersHandler = $ordersHandler;
    }

    public function processPayment($orderId, User $user, string $stripeToken, $totalPrice)
    {
        $totalInCents = $totalPrice * 100;

        $orderMeta = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => $user->name,
            'user_phone' => $user->phone,
            'order_id' => $orderId,
        ];

        return $this->strategy->pay($orderMeta, $totalInCents, $stripeToken);
    }

    public function refund($payment, $amount)
    {
        $refundAmountInCents = $amount * 100;

        $paymentIntent =  $this->strategy->getPaymentIntent($payment['online_payment_id']);
        $chargeId = $paymentIntent['charges']['data'][0]['id'];

        $refund =  $this->strategy->refund($chargeId, $refundAmountInCents);

        return $refund;
    }
}
