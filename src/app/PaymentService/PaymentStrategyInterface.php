<?php

namespace App\PaymentService;

interface PaymentStrategyInterface
{
    public function pay(array $orderMeta, float $totalPrice, string $stripeToken);
}
