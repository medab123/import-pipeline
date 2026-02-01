<?php

namespace App\Http\Controllers;

use App\Enums\ToastNotificationVariant;

abstract class Controller
{
    public function toast(string $message, ToastNotificationVariant $variant = ToastNotificationVariant::Default): self
    {
        $notifications = session()->get('toastNotifications', []);
        $notifications[] = [
            'title' => $message,
            'message' => '',
            'variant' => $variant->value,
        ];
        session()->flash('toastNotifications', $notifications);

        return $this;
    }
}
