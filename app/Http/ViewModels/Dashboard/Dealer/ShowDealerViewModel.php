<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Dealer;

use App\Models\Dealer;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class ShowDealerViewModel extends ViewModel
{
    public function __construct(private readonly Dealer $dealer) {}

    public function dealer(): DealerViewModel
    {
        return new DealerViewModel($this->dealer);
    }

    public function recentTransactions(): array
    {
        return $this->dealer->paymentTransactions()
            ->latest('paid_at')
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type->value,
                'amount' => $t->amount,
                'status' => $t->status->value,
                'payment_method' => $t->payment_method,
                'reference' => $t->reference,
                'paid_at' => $t->paid_at?->toISOString(),
                'formatted_paid_at' => $t->paid_at?->format('M d, Y H:i'),
                'created_at' => $t->created_at->toISOString(),
            ])
            ->toArray();
    }

    public function scraps(): array
    {
        return $this->dealer->scraps()
            ->latest()
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'ftp_file_path' => $s->ftp_file_path,
                'provider' => $s->provider,
                'created_at' => $s->created_at->toISOString(),
                'formatted_created_at' => $s->created_at->format('M d, Y H:i'),
            ])
            ->toArray();
    }
}
