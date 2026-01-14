<?php

namespace ERPClient\Mail;

use ERPClient\Models\Order;
use Illuminate\Mail\Mailable;

class NewOrderNotification extends Mailable
{
    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build()
    {
        return $this->subject('Nouă solicitare #'.$this->order->id)
            ->view('erp-city-client::emails.orders.new');
    }
}
