<?php

namespace App\Http\Controllers;

use App\Enums\ToastNotificationVariant;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

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
