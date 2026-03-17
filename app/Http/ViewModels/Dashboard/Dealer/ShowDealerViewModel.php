<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Dealer;

use App\Models\Dealer;
use Elaitech\Import\Models\ImportPipeline;
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

    public function importPipelines(): array
    {
        return ImportPipeline::where('target_id', $this->dealer->id)
            ->where('organization_uuid', $this->dealer->organization_uuid)
            ->latest()
            ->get()
            ->map(fn (ImportPipeline $p) => [
                'id'               => $p->id,
                'name'             => $p->name,
                'is_active'        => $p->is_active,
                'token'            => $p->token,
                'last_executed_at' => $p->last_executed_at?->format('M d, Y H:i'),
                'next_execution_at'=> $p->next_execution_at?->format('M d, Y H:i'),
                'frequency'        => $p->frequency?->value ?? null,
            ])
            ->toArray();
    }
}
