<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewOrderMailToIndividualSeller extends Mailable
{
    use Queueable, SerializesModels;

    public $siteUrl;
    public $sellerName;
    public $customerName;
    public $customerPhone;
    public $orderItems;

    public function __construct($data)
    {
        $this->siteUrl = config('app.SITE_URL');
        $this->sellerName = $data['sellerName'];
        $this->customerName = $data['customerName'];
        $this->customerPhone = $data['customerPhone'];

        $this->orderItems = $data['orderItems'];
    }

    public function build()
    {
        return $this->view('mails.new-order-individual-seller')
            ->subject('New order received - Medicine Agent');
    }
}
