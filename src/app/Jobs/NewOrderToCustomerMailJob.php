<?php

namespace App\Jobs;

use App\Mail\NewOrderMailToCustomer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NewOrderToCustomerMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $details;
    protected $email;
    protected $name;
    protected $orderDate;

    public function __construct($details, $email, $name, $orderDate)
    {
        $this->details = $details;
        $this->email = $email;
        $this->name = $name;
        $this->orderDate = $orderDate;
    }

    public function handle()
    {
        Mail::to($this->email)
            ->send(new NewOrderMailToCustomer([
                'customer_name' => $this->name,
                'order_date' => $this->orderDate,
                'orderItems' => $this->details,
            ]));
    }
}
