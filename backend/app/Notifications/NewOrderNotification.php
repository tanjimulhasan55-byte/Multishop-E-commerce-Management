<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Order $order)
    {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New order {$this->order->number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your shop received a new order ({$this->order->number}).")
            ->line("Total: {$this->order->currency} {$this->order->total}")
            ->action('View orders', config('services.frontend_url').'/owner/orders');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->getKey(),
            'number' => $this->order->number,
            'total' => (string) $this->order->total,
            'shop_id' => $this->order->shop_id,
        ];
    }
}
