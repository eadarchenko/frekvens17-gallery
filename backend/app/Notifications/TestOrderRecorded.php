<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TestOrderRecorded extends Notification
{
    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $o = $this->order;
        $mail = (new MailMessage)->mailer('log')->subject('LOCAL TEST order recorded - no payment')
            ->greeting('Test order recorded')->line('LOCAL DEVELOPMENT ONLY. No payment was collected. Do not dispatch goods.')
            ->line('Reference: '.$o->reference);
        foreach ($o->items as $l) {
            $mail->line($l->quantity.' x '.$l->title.' / '.$l->variant);
        }

        return $mail->line('Test total: '.number_format($o->total_cents / 100, 2).' '.$o->currency)
            ->line('This message is written to the local log only, not sent to an inbox.');
    }
}
