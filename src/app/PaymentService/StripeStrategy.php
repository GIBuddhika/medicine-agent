<?php

namespace App\PaymentService;

use App\PaymentService\PaymentStrategyInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use Stripe\Card;
use Stripe\Exception\CardException;
use Stripe\Exception\OAuth\InvalidRequestException;
use Stripe\StripeClient;

class StripeStrategy implements PaymentStrategyInterface
{

    private $stripeClient;

    public function __construct()
    {
        $this->stripeClient = new StripeClient(config('app.STRIPE_SECRET'));
    }

    public function pay(array $metaData, float $amount, string $stripeToken)
    {
        try {
            $customer = $this->createCustomer($metaData);
            $card =  $this->createSource($customer['id'], $stripeToken);
            $paymentIntent = $this->createPaymentIntent($customer['id'], $metaData, $amount, $card['id']);
            return $paymentIntent['id'];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getPaymentIntent($paymentIntentId)
    {
        try {
            $paymentIntent =  $this->stripeClient->paymentIntents->retrieve(
                $paymentIntentId,
                []
            );
            return $paymentIntent;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function refundTotalCharge($chargeId)
    {
        try {
            $refund =  $this->stripeClient->refunds->create([
                'charge' => $chargeId
            ]);
            return $refund;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function createCustomer(array $user)
    {
        try {
            $stripeCustomer =  $this->stripeClient->customers->create([
                'email' => $user['user_email'],
                'name' => $user['user_name'],
                'phone' => $user['user_phone'],
                'metadata' => [
                    "user_id" => $user['user_id']
                ],
                'address' => [
                    'line1' => '510 Townsend St',
                    'postal_code' => '98140',
                    'city' => 'San Francisco',
                    'state' => 'CA',
                    'country' => 'US',
                ],
            ]);
            return $stripeCustomer;
        } catch (CardException $ex) {
            Log::channel('stripe')->info($ex);
            throw new CardException($ex);
        } catch (InvalidRequestException $ex) {
            Log::channel('stripe')->info($ex);
            throw new InvalidRequestException($ex);
        } catch (Exception $ex) {
            throw new Exception($ex);
        }
    }

    private function createSource(string $customerId, string $stripeToken): Card
    {
        try {
            $card =  $this->stripeClient->customers->createSource(
                $customerId,
                [
                    'source' => $stripeToken,
                ]
            );

            return $card;
        } catch (CardException $ex) {
            Log::channel('stripe')->info($ex);
            throw new CardException($ex);
        } catch (InvalidRequestException $ex) {
            Log::channel('stripe')->info($ex);
            throw new InvalidRequestException($ex);
        } catch (Exception $ex) {
            throw new Exception($ex);
        }
    }

    private function createPaymentIntent(string $customerId, array $metaData, float $amount, string $cardId)
    {
        try {
            $charge =  $this->stripeClient->paymentIntents->create([
                'amount' => $amount,
                'currency' => 'inr',
                'customer' => $customerId,
                'payment_method' => $cardId,
                'metadata' => $metaData,
                'description' => 'Payment success',
                'confirm' => true
            ]);
            return $charge;
        } catch (CardException $ex) {
            Log::channel('stripe')->info($ex);
            throw new CardException($ex);
        } catch (InvalidRequestException $ex) {
            Log::channel('stripe')->info($ex);
            throw new InvalidRequestException($ex);
        } catch (Exception $ex) {
            throw new Exception($ex);
        }
    }
}
