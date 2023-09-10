<?php

namespace App\Jobs;

use App\Mail\NewOrderMailToIndividualSeller;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NewOrderToIndividualSellerMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $details;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->details['email'])
            ->send(new NewOrderMailToIndividualSeller([
                'sellerName' => $this->details['sellerName'],
                'customerName' => $this->details['customerName'],
                'customerPhone' => $this->details['customerPhone'],
                'orderItems' => $this->details['orderItems'],
            ]));
    }
}
