<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderRenewMailToSeller extends Mailable
{
    use Queueable, SerializesModels;

    public $siteUrl;
    public $sellerName;
    public $customerName;
    public $customerPhone;
    public $orderItem;

    public function __construct($data)
    {
        $this->siteUrl = config('app.SITE_URL');
        $this->sellerName = $data['sellerName'];
        $this->customerName = $data['customerName'];
        $this->customerPhone = $data['customerPhone'];

        $this->orderItem = $data['orderItem'];
    }

    public function build()
    {
        return $this->view('mails.renew-order-seller')
            ->subject('Order extended - Medicine Agent');
    }
}
