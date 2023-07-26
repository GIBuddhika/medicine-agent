<?php

namespace App\Http\Handlers;

use App\Constants\OrderStatusConstants;
use App\Constants\SessionConstants;
use App\Constants\UserRoleConstants;
use App\Models\ItemOrder;
use App\Models\Order;
use App\PaymentService\PaymentService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;

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

        if (isset($orderItem['note'])) {
            $itemOrder->note = $orderItem['note'];
        }

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
        if (isset($data['duration'])) {
            $itemOrder->duration = $data['duration'];
        }
        if (isset($data['received_at'])) {
            $itemOrder->received_at = $data['received_at'];
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

    public function markAsReceived($itemOrderId, $data)
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

            $itemOrder = DB::transaction(function () use ($itemOrder, $data) {
                if (Carbon::parse($itemOrder->created_at)->addMonths($itemOrder->duration)->isPast()) {

                    $orderCreatedAt = Carbon::parse($itemOrder->created_at);
                    $now = Carbon::now();
                    $monthsDifference = $now->diffInMonths($orderCreatedAt);

                    $paymentData = [
                        'user_id' => $itemOrder->order->user_id,
                        'order_id' => $itemOrder->order_id,
                        'item_order_id' => $itemOrder->id,
                        'payment_type' => 'shop', //customer paid while hand over the item to admin
                        'payment_amount' => null,
                        'duration' => $monthsDifference,
                        'online_payment_id' => null,
                    ];

                    if ($data['isChargedForDueItem'] == true) {
                        $paymentData['payment_amount'] = $data['chargedAmount'];
                        $log = "Item is overdue by " . $monthsDifference . " months."
                            . " Admin has charged " . $data['chargedAmount'] . "LKR"
                            . " from customer on " . $now->format('Y M d h.ia')
                            . " while hand over the item";
                    } else {
                        $log = "Item is overdue by " . $monthsDifference . " months."
                            . " But Admin did not charge for overdue months."
                            . " Customer hand over the item on " . $now->format('Y M d h.ia');
                    }

                    $paymentData['log'] = $log;
                    $this->getPaymentsHandler()->create($paymentData);

                    $itemOrderData['duration'] = $itemOrder->duration + $monthsDifference;
                }

                $itemOrderData['status'] = OrderStatusConstants::RECEIVED;
                $itemOrderData['received_at'] = Carbon::now();

                $this->updateOrderItem($itemOrder, $itemOrderData);

                return $itemOrder;
            });

            return $itemOrder;
        } catch (ModelNotFoundException $ex) {
            throw new ModelNotFoundException();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function markAsCancelled($itemOrderId, $orderData)
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

            $orderItem = $itemOrderQ->firstOrFail();

            $orderItem = DB::transaction(function () use ($orderItem, $orderData) {

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

    private function getPaymentsHandler(): PaymentsHandler
    {
        return app(PaymentsHandler::class);
    }

    private function getRefundsHandler(): RefundsHandler
    {
        return app(RefundsHandler::class);
    }

    private function getPaymentService(): PaymentService
    {
        return app(PaymentService::class);
    }
}
