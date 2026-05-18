<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\Dealer;

use App\Enums\DealerStatus;
use App\Enums\ToastNotificationVariant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Dealer\StoreDealerRequest;
use App\Http\Requests\Dashboard\Dealer\UpdateDealerRequest;
use App\Http\ViewModels\Dashboard\Dealer\CreateDealerViewModel;
use App\Http\ViewModels\Dashboard\Dealer\EditDealerViewModel;
use App\Http\ViewModels\Dashboard\Dealer\ListDealerViewModel;
use App\Http\ViewModels\Dashboard\Dealer\ShowDealerViewModel;
use App\Models\Dealer;
use App\Models\DealerFbmpToken;
use App\Services\FbmpTokenService;
use Elaitech\Import\Models\ImportPipeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Response as InertiaResponse;

final class DealerController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', Dealer::class);

        $query = Dealer::where('organization_uuid', auth()->user()->organization_uuid)
            ->withCount(['paymentTransactions', 'scraps'])
            ->withExists(['paymentTransactions as is_paid' => function ($query) {
                $query->where('type', 'dealer_payment')
                    ->where(function ($q) {
                        $q->where(function ($sub) {
                            $sub->whereRaw("dealers.payment_period = 'month'")
                                ->whereYear('payment_transactions.created_at', now()->year)
                                ->whereMonth('payment_transactions.created_at', now()->month);
                        })->orWhere(function ($sub) {
                            $sub->whereRaw("dealers.payment_period = 'year'")
                                ->whereYear('payment_transactions.created_at', now()->year);
                        });
                    });
            }]);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('posting_address', 'like', '%'.$request->search.'%')
                    ->orWhereJsonContains('website_urls', $request->search);
            });
        }

        $dealers = $query->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return inertia('Dashboard/Dealer/Index', new ListDealerViewModel($dealers, $request->search));
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', Dealer::class);

        return inertia('Dashboard/Dealer/Create', new CreateDealerViewModel);
    }

    public function store(StoreDealerRequest $request): RedirectResponse
    {
        $this->authorize('create', Dealer::class);

        $dealer = Dealer::create([
            ...$request->validated(),
            'organization_uuid' => auth()->user()->organization_uuid,
            'status' => DealerStatus::Pending->value,
        ]);

        $dealer->resolveStatus();

        $this->toast('Dealer created successfully.');

        return redirect()->route('dashboard.dealers.index');
    }

    public function show(Dealer $dealer): InertiaResponse
    {
        $this->authorize('view', $dealer);

        return inertia('Dashboard/Dealer/Show', new ShowDealerViewModel($dealer));
    }

    public function edit(Dealer $dealer): InertiaResponse
    {
        $this->authorize('update', $dealer);

        return inertia('Dashboard/Dealer/Edit', new EditDealerViewModel($dealer));
    }

    public function update(UpdateDealerRequest $request, Dealer $dealer): RedirectResponse
    {
        $this->authorize('update', $dealer);

        $dealer->update($request->validated());

        $dealer->fresh()->resolveStatus();

        $this->toast('Dealer updated successfully.');

        return redirect()->route('dashboard.dealers.show', $dealer);
    }

    public function destroy(Dealer $dealer, FbmpTokenService $fbmpTokenService): RedirectResponse
    {
        $this->authorize('delete', $dealer);

        // Best-effort: revoke every FBMP token on the external API. Failures
        // are logged inside the service but must not block dealer deletion.
        foreach ($dealer->fbmpTokens as $token) {
            $fbmpTokenService->delete($token->token);
        }

        DB::transaction(function () use ($dealer): void {
            // Delete pipelines targeting this dealer; pipeline_configs,
            // executions and logs cascade via FK on import_pipelines.id.
            ImportPipeline::where('target_id', $dealer->id)
                ->where('organization_uuid', $dealer->organization_uuid)
                ->get()
                ->each(fn (ImportPipeline $pipeline) => $pipeline->delete());

            // Scraps, payment_transactions and dealer_fbmp_tokens cascade
            // via FK on dealers.id.
            $dealer->delete();
        });

        $this->toast('Dealer, pipelines and FBMP tokens deleted successfully.', ToastNotificationVariant::Destructive);

        return back(303);
    }

    /**
     * Generate a new FBMP token for the dealer. Requires "manage fbmp token".
     */
    public function storeFbmpToken(Dealer $dealer, FbmpTokenService $fbmpTokenService): RedirectResponse
    {
        $this->authorize('update', $dealer);

        if (! auth()->user()->can('manage fbmp token')) {
            $this->toast('You do not have permission to create FBMP tokens.', ToastNotificationVariant::Destructive);

            return back(303);
        }

        $userEmail = $this->buildFbmpUserEmail($dealer, $dealer->fbmpTokens()->count());
        $row = $fbmpTokenService->generateForDealer($dealer, $userEmail);

        if (! $row) {
            $this->toast('Failed to generate FBMP token. Check the logs for details.', ToastNotificationVariant::Destructive);

            return back(303);
        }

        $this->toast('FBMP token generated successfully.');

        return back(303);
    }

    /**
     * Regenerate a specific token. Allowed for anyone who can update the dealer.
     */
    public function regenerateFbmpToken(Dealer $dealer, DealerFbmpToken $token, FbmpTokenService $fbmpTokenService): RedirectResponse
    {
        $this->authorize('update', $dealer);
        $this->ensureTokenBelongsToDealer($token, $dealer);

        if (! $fbmpTokenService->regenerateToken($token)) {
            $this->toast('Failed to regenerate FBMP token. Check the logs for details.', ToastNotificationVariant::Destructive);

            return back(303);
        }

        $this->toast('FBMP token regenerated successfully.');

        return back(303);
    }

    /**
     * Revoke a specific token. Requires "manage fbmp token".
     */
    public function revokeFbmpToken(Dealer $dealer, DealerFbmpToken $token, FbmpTokenService $fbmpTokenService): RedirectResponse
    {
        $this->authorize('update', $dealer);
        $this->ensureTokenBelongsToDealer($token, $dealer);

        if (! auth()->user()->can('manage fbmp token')) {
            $this->toast('You do not have permission to revoke FBMP tokens.', ToastNotificationVariant::Destructive);

            return back(303);
        }

        if (! $fbmpTokenService->revokeToken($token)) {
            $this->toast('Failed to revoke FBMP token. Check the logs for details.', ToastNotificationVariant::Destructive);

            return back(303);
        }

        $this->toast('FBMP token revoked successfully.', ToastNotificationVariant::Destructive);

        return back(303);
    }

    private function ensureTokenBelongsToDealer(DealerFbmpToken $token, Dealer $dealer): void
    {
        abort_if($token->dealer_id !== $dealer->id, 404);
    }

    /**
     * Build a unique email identifier for the FBMP API. The first token uses
     * the slug as-is for backwards compatibility; additional tokens append a
     * short random suffix so the API receives a distinct user per token.
     */
    private function buildFbmpUserEmail(Dealer $dealer, int $existingCount = 0): string
    {
        $slug = str($dealer->name)->slug('_')->value();

        if ($existingCount === 0) {
            return $slug.'@gmail.com';
        }

        return $slug.'_'.strtolower(Str::random(6)).'@gmail.com';
    }
}
