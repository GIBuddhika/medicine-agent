<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewOrderMailToCustomer extends Mailable
{
    use Queueable, SerializesModels;

    public $siteUrl;
    public $customerName;
    public $orderDateFrom;
    public $orderDateTo;
    public $orderItems;

    public function __construct($data)
    {
        $this->siteUrl = config('app.SITE_URL');
        $this->customerName = $data['customer_name'];
        $this->orderDateFrom = $data['order_date'];
        $this->orderDateTo = Carbon::parse($data['order_date'])->addDay()->format('Y-m-d');
        $this->orderItems = $data['orderItems'];
    }

    public function build()
    {
        return $this->view('mails.new-order-customer')
            ->subject('New order placed - Medicine Agent');
    }
}
