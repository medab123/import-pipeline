<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PaymentTransaction;
use App\Models\User;

class PaymentTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->organization_uuid;
    }

    public function view(User $user, PaymentTransaction $transaction): bool
    {
        return $transaction->organization_uuid === $user->organization_uuid;
    }

    public function create(User $user): bool
    {
        return (bool) $user->organization_uuid;
    }

    public function update(User $user, PaymentTransaction $transaction): bool
    {
        return $transaction->organization_uuid === $user->organization_uuid;
    }

    public function delete(User $user, PaymentTransaction $transaction): bool
    {
        return $transaction->organization_uuid === $user->organization_uuid;
    }
}
