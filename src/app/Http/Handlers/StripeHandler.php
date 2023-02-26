<?php

namespace App\Http\Handlers;

use Exception;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;

class StripeHandler
{
    public function createStripeCustomer($data)
    {
        try {
            $stripe = new StripeClient(config('app.STRIPE_SECRET'));
            $stripeCustomer =  $stripe->customers->create([
                'email' => $data->email,
                'name' => $data->name,
                'phone' => $data->name,
                'metadata' => [
                    "user_id" => $data->id
                ]
            ]);
            return $stripeCustomer;
        } catch (ApiErrorException $ex) {
            Log::channel('stripe')->info($ex);
            throw new Exception($ex);
        } catch (InvalidRequestException $ex) {
            Log::channel('stripe')->info($ex);
            throw new Exception($ex);
        }
    }
}
