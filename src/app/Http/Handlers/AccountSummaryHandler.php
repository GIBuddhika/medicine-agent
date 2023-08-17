<?php

namespace App\Http\Handlers;

use App\Constants\SessionConstants;
use App\Constants\UserRoleConstants;
use App\Models\ItemOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AccountSummaryHandler
{
    public function filter($data)
    {
        $user = session(SessionConstants::User);
        $userRole = session(SessionConstants::UserRole);

        $itemOrderQ = ItemOrder::with('item.shop.shopAdmins','item.files', 'order', 'payments', 'refunds');
        // ->where('id', $itemOrderId);

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

        if (isset($data['date_from']) && isset($data['date_to'])) {
            $itemOrderQ->whereBetween('created_at', [$data['date_from'], Carbon::parse($data['date_to'])->addDay()]);
        }

        if (isset($data['personal_only'])) {
            if ($data['personal_only'] === "true") {
                $itemOrderQ->whereHas('item', function ($query1) use ($data) {
                    $query1->whereNotNull('personal_listing_id');
                });
            } else if (isset($data['shop_id'])) {
                $itemOrderQ->whereHas('item', function ($query1) use ($data) {
                    $query1->where('shop_id', $data['shop_id']);
                });
            }
        }

        $itemOrdersAll = $itemOrderQ->orderBy('created_at')->get();

        if (isset($data['page']) && isset($data['perPage'])) {
            $itemOrders = $itemOrderQ->skip(($data['page'] - 1) * $data['perPage'])->take($data['perPage'])->get();
        } else {
            $itemOrders = $itemOrdersAll;
        }

        $totalSale = 0;
        $totalRefunds = 0;

        foreach ($itemOrders as $itemOrder) {
            $totalSale += $itemOrder->payments->sum('payment_amount');
            $totalRefunds += $itemOrder->refunds->sum('refund_amount');
        }

        return [
            'total_sale' => $totalSale,
            'total_refunds' => $totalRefunds,
            'orders' => $itemOrders,
            'total' => count($itemOrdersAll)
        ];
    }
}
