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
use App\Services\FbmpTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function destroy(Dealer $dealer): RedirectResponse
    {
        $this->authorize('delete', $dealer);

        $dealer->delete();

        $this->toast('Dealer deleted successfully.', ToastNotificationVariant::Destructive);

        return back(303);
    }

    public function generateToken(Dealer $dealer, FbmpTokenService $fbmpTokenService): RedirectResponse
    {
        $this->authorize('update', $dealer);

        if (! empty($dealer->fbmp_app_access_token)) {
            $this->toast('Dealer already has an FBMP token. Use Regenerate instead.', ToastNotificationVariant::Destructive);

            return back(303);
        }

        $token = $fbmpTokenService->generateAndSave($dealer, $this->buildFbmpUserEmail($dealer));

        if (! $token) {
            $this->toast('Failed to generate FBMP token. Check the logs for details.', ToastNotificationVariant::Destructive);

            return back(303);
        }

        $this->toast('FBMP token generated successfully.');

        return back(303);
    }

    public function regenerateToken(Dealer $dealer, FbmpTokenService $fbmpTokenService): RedirectResponse
    {
        $this->authorize('update', $dealer);

        if (empty($dealer->fbmp_app_access_token)) {
            $this->toast('Dealer has no FBMP token to regenerate. Generate one first.', ToastNotificationVariant::Destructive);

            return back(303);
        }

        $token = $fbmpTokenService->regenerateAndSave($dealer);

        if (! $token) {
            $this->toast('Failed to regenerate FBMP token. Check the logs for details.', ToastNotificationVariant::Destructive);

            return back(303);
        }

        $this->toast('FBMP token regenerated successfully.');

        return back(303);
    }

    public function revokeToken(Dealer $dealer, FbmpTokenService $fbmpTokenService): RedirectResponse
    {
        $this->authorize('update', $dealer);

        if (empty($dealer->fbmp_app_access_token)) {
            $this->toast('Dealer has no FBMP token to revoke.', ToastNotificationVariant::Destructive);

            return back(303);
        }

        $revoked = $fbmpTokenService->revokeAndClear($dealer);

        if (! $revoked) {
            $this->toast('Failed to revoke FBMP token. Check the logs for details.', ToastNotificationVariant::Destructive);

            return back(303);
        }

        $this->toast('FBMP token revoked successfully.', ToastNotificationVariant::Destructive);

        return back(303);
    }

    /**
     * Build a unique email identifier for the FBMP API based on the dealer name.
     */
    private function buildFbmpUserEmail(Dealer $dealer): string
    {
        $slug = str($dealer->name)->slug('_')->value();

        return $slug.'@gmail.com';
    }
}
