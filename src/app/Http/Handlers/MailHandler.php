<?php

namespace App\Http\Handlers;

use App\Jobs\NewOrderToCustomerMailJob;
use App\Jobs\NewOrderToIndividualSellerMailJob;
use App\Jobs\NewOrderToSellerMailJob;
use App\Jobs\OrderRenewToSellerMailJob;
use App\Mail\OrderRenewMailToSeller;
use App\Models\ItemOrder;
use Carbon\Carbon;

class MailHandler
{
    public  function dispatchOrderSuccessEmails($order)
    {
        $orderItems = ItemOrder::with('item.shop.user')
            ->where('order_id', $order->id)
            ->get();

        $shopOwners = [];
        $shopAdminsArray = [];
        $individualSellers = [];
        $orderItemsEmailsToCustomer = [];

        $customerName = $orderItems[0]->order->user->name;
        $customerPhone = $orderItems[0]->order->user->phone;

        foreach ($orderItems as $orderItem) {

            $orderItemsDataForEmail = [
                'id' => $orderItem->id,
                'name' => $orderItem->item->name,
                'quantity' => $orderItem->quantity,
                'duration' => $orderItem->duration,
                'image_src' => 'http://localhost:8001/storage/public/' . $orderItem->item->mainImage()->location,
                'shopName' => $orderItem->item->shop ? $orderItem->item->shop->name : null,
                'sellerName' => $orderItem->item->shop ? $orderItem->item->shop->user->name : $orderItem->item->user->name,
                'sellerPhone' => $orderItem->item->shop ? $orderItem->item->shop->phone : $orderItem->item->user->phone,
            ];

            if ($orderItem->item->shop) {

                $customerKey = array_search($orderItem->item->shop->id, array_column($orderItemsEmailsToCustomer, 'shop_id'));
                if ($customerKey === false) {
                    $orderItemsEmailsToCustomer[] = [
                        'shop_id' => $orderItem->item->shop->id,
                        'shop_name' => $orderItem->item->shop->name,
                        'shop_phone' => $orderItem->item->shop->phone,
                        'shop_address' => $orderItem->item->shop->address,
                        'orderItems' => [
                            $orderItemsDataForEmail
                        ]
                    ];
                } else {
                    $orderItemsEmailsToCustomer[$customerKey]['orderItems'][] = $orderItemsDataForEmail;
                }

                $key = array_search($orderItem->item->user->email, array_column($shopOwners, 'email'));
                if ($key === false) {
                    $shopOwners[] = [
                        'email' => $orderItem->item->user->email,
                        'sellerName' => $orderItem->item->user->name,
                        'customerName' => $customerName,
                        'customerPhone' => $customerPhone,
                        'orderItems' => [
                            $orderItem->item->shop->name => [
                                $orderItemsDataForEmail
                            ],
                        ]
                    ];
                } else {
                    $shopOwners[$key]['orderItems'][$orderItem->item->shop->name][] = $orderItemsDataForEmail;
                }

                $shopAdmins = $orderItem->item->shop->shopAdmins;

                foreach ($shopAdmins as $shopAdmin) {
                    $shopAdminKey = array_search($shopAdmin->email, array_column($shopAdminsArray, 'email'));

                    if ($shopAdminKey === false) {
                        $shopAdminsArray[] = [
                            'email' => $shopAdmin->email,
                            'sellerName' => $shopAdmin->name,
                            'customerName' => $customerName,
                            'customerPhone' => $customerPhone,
                            'orderItems' => [
                                $orderItem->item->shop->name => [
                                    $orderItemsDataForEmail
                                ],
                            ]
                        ];
                    } else {
                        $shopAdminsArray[$shopAdminKey]['orderItems'][$orderItem->item->shop->name][] = $orderItemsDataForEmail;
                    }
                }
            } else {

                $customerKey = array_search($orderItem->item->user->id, array_column($orderItemsEmailsToCustomer, 'shop_id'));
                if ($customerKey === false) {
                    $orderItemsEmailsToCustomer[] = [
                        'shop_id' => $orderItem->item->user->id,
                        'shop_name' => $orderItem->item->user->name,
                        'shop_phone' => $orderItem->item->user->phone,
                        'shop_address' => $orderItem->item->personalListing->address,
                        'orderItems' => [
                            $orderItemsDataForEmail
                        ]
                    ];
                } else {
                    $orderItemsEmailsToCustomer[$customerKey]['orderItems'][] = $orderItemsDataForEmail;
                }


                $individualSellerKey = array_search($orderItem->item->user->email, array_column($individualSellers, 'email'));

                if ($individualSellerKey === false) {
                    $individualSellers[] = [
                        'email' => $orderItem->item->user->email,
                        'sellerName' => $orderItem->item->user->name,
                        'customerName' => $customerName,
                        'customerPhone' => $customerPhone,
                        'orderItems' => [
                            $orderItemsDataForEmail
                        ],
                    ];
                } else {
                    $individualSellers[$individualSellerKey]['orderItems'][] = $orderItemsDataForEmail;
                }
            }
        }
        $emailsToShopUsers = array_merge($shopOwners, $shopAdminsArray);

        foreach ($emailsToShopUsers as $mail) {
            dispatch(new NewOrderToSellerMailJob($mail));
        }

        foreach ($individualSellers as $mail) {
            dispatch(new NewOrderToIndividualSellerMailJob($mail));
        }

        dispatch(new NewOrderToCustomerMailJob($orderItemsEmailsToCustomer, $order->user->email, $order->user->name, Carbon::parse($order->created_at)->format('Y-m-d')));
    }

    public function dispatchOrderRenewEmails($order, $orderItem, $paidAmout, $extendedDuraiton)
    {
        $shopOwners = [];
        $shopAdminsArray = [];
        $individualSellers = [];
        $orderItemsEmailsToCustomer = [];

        $customerName = $order->user->name;
        $customerPhone = $order->user->phone;

        $orderItemsDataForEmail = [
            'id' => $orderItem->pivot->id,
            'name' => $orderItem->name,
            'quantity' => $orderItem->pivot->quantity,
            'duration' => $orderItem->pivot->duration,
            'image_src' => 'http://localhost:8001/storage/public/' . $orderItem->mainImage()->location,
            'shopName' => $orderItem->shop ? $orderItem->shop->name : null,
            'sellerName' => $orderItem->shop ? $orderItem->shop->user->name : $orderItem->user->name,
            'sellerPhone' => $orderItem->shop ? $orderItem->shop->phone : $orderItem->user->phone,
            'extendedDuration' => $extendedDuraiton,
            'paidAmount' => $paidAmout,
        ];

        if ($orderItem->shop) {

            $customerKey = array_search($orderItem->shop->id, array_column($orderItemsEmailsToCustomer, 'shop_id'));
            if ($customerKey === false) {
                $orderItemsEmailsToCustomer[] = [
                    'shop_id' => $orderItem->shop->id,
                    'shop_name' => $orderItem->shop->name,
                    'shop_phone' => $orderItem->shop->phone,
                    'shop_address' => $orderItem->shop->address,
                    'orderItem' => $orderItemsDataForEmail
                ];
            } else {
                $orderItemsEmailsToCustomer[$customerKey]['orderItem'] = $orderItemsDataForEmail;
            }

            $key = array_search($orderItem->user->email, array_column($shopOwners, 'email'));
            if ($key === false) {
                $shopOwners[] = [
                    'email' => $orderItem->user->email,
                    'sellerName' => $orderItem->user->name,
                    'customerName' => $customerName,
                    'customerPhone' => $customerPhone,
                    'orderItem' => $orderItemsDataForEmail
                ];
            } else {
                $shopOwners[$key]['orderItem'] = $orderItemsDataForEmail;
            }

            $shopAdmins = $orderItem->shop->shopAdmins;

            foreach ($shopAdmins as $shopAdmin) {
                $shopAdminKey = array_search($shopAdmin->email, array_column($shopAdminsArray, 'email'));

                if ($shopAdminKey === false) {
                    $shopAdminsArray[] = [
                        'email' => $shopAdmin->email,
                        'sellerName' => $shopAdmin->name,
                        'customerName' => $customerName,
                        'customerPhone' => $customerPhone,
                        'orderItem' => $orderItemsDataForEmail
                    ];
                } else {
                    $shopAdminsArray[$shopAdminKey]['orderItem'] = $orderItemsDataForEmail;
                }
            }
        } else {

            $customerKey = array_search($orderItem->user->id, array_column($orderItemsEmailsToCustomer, 'shop_id'));
            if ($customerKey === false) {
                $orderItemsEmailsToCustomer[] = [
                    'shop_id' => $orderItem->user->id,
                    'shop_name' => $orderItem->user->name,
                    'shop_phone' => $orderItem->user->phone,
                    'shop_address' => $orderItem->personalListing->address,
                    'orderItem' => $orderItemsDataForEmail
                ];
            } else {
                $orderItemsEmailsToCustomer[$customerKey]['orderItem'] = $orderItemsDataForEmail;
            }


            $individualSellerKey = array_search($orderItem->user->email, array_column($individualSellers, 'email'));

            if ($individualSellerKey === false) {
                $individualSellers[] = [
                    'email' => $orderItem->user->email,
                    'sellerName' => $orderItem->user->name,
                    'customerName' => $customerName,
                    'customerPhone' => $customerPhone,
                    'orderItem' => $orderItemsDataForEmail
                ];
            } else {
                $individualSellers[$individualSellerKey]['orderItem'] = $orderItemsDataForEmail;
            }
        }

        $emailsToShopUsers = array_merge($shopOwners, $shopAdminsArray, $individualSellers);

        foreach ($emailsToShopUsers as $mail) {
            dispatch(new OrderRenewToSellerMailJob($mail));
        }
    }
}
