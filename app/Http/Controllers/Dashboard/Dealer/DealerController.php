<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\Dealer;

use App\Enums\ToastNotificationVariant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Dealer\StoreDealerRequest;
use App\Http\Requests\Dashboard\Dealer\UpdateDealerRequest;
use App\Http\ViewModels\Dashboard\Dealer\CreateDealerViewModel;
use App\Http\ViewModels\Dashboard\Dealer\EditDealerViewModel;
use App\Http\ViewModels\Dashboard\Dealer\ListDealerViewModel;
use App\Http\ViewModels\Dashboard\Dealer\ShowDealerViewModel;
use App\Models\Dealer;
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
                    ->orWhere('website_url', 'like', '%'.$request->search.'%');
            });
        }

        $dealers = $query->orderBy('name')
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

        Dealer::create([
            ...$request->validated(),
            'organization_uuid' => auth()->user()->organization_uuid,
        ]);

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
}
