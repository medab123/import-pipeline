<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\PaymentTransaction;

use App\Enums\ToastNotificationVariant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\PaymentTransaction\StorePaymentTransactionRequest;
use App\Http\Requests\Dashboard\PaymentTransaction\UpdatePaymentTransactionRequest;
use App\Http\ViewModels\Dashboard\PaymentTransaction\CreatePaymentTransactionViewModel;
use App\Http\ViewModels\Dashboard\PaymentTransaction\EditPaymentTransactionViewModel;
use App\Http\ViewModels\Dashboard\PaymentTransaction\ListPaymentTransactionViewModel;
use App\Http\ViewModels\Dashboard\PaymentTransaction\ShowPaymentTransactionViewModel;
use App\Models\PaymentTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

final class PaymentTransactionController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', PaymentTransaction::class);

        $query = PaymentTransaction::where('organization_uuid', auth()->user()->organization_uuid)
            ->with('dealer:id,name');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('reference', 'like', '%'.$request->search.'%')
                    ->orWhere('payment_method', 'like', '%'.$request->search.'%')
                    ->orWhereHas('dealer', function ($dq) use ($request) {
                        $dq->where('name', 'like', '%'.$request->search.'%');
                    });
            });
        }

        $transactions = $query->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return inertia('Dashboard/PaymentTransaction/Index', new ListPaymentTransactionViewModel($transactions, $request->search));
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', PaymentTransaction::class);

        return inertia('Dashboard/PaymentTransaction/Create', new CreatePaymentTransactionViewModel);
    }

    public function store(StorePaymentTransactionRequest $request): RedirectResponse
    {
        $this->authorize('create', PaymentTransaction::class);

        PaymentTransaction::create([
            ...$request->validated(),
            'organization_uuid' => auth()->user()->organization_uuid,
        ]);

        $this->toast('Payment transaction created successfully.');

        return redirect()->route('dashboard.payment-transactions.index');
    }

    public function show(PaymentTransaction $paymentTransaction): InertiaResponse
    {
        $this->authorize('view', $paymentTransaction);

        $paymentTransaction->load('dealer:id,name');

        return inertia('Dashboard/PaymentTransaction/Show', new ShowPaymentTransactionViewModel($paymentTransaction));
    }

    public function edit(PaymentTransaction $paymentTransaction): InertiaResponse
    {
        $this->authorize('update', $paymentTransaction);

        $paymentTransaction->load('dealer:id,name');

        return inertia('Dashboard/PaymentTransaction/Edit', new EditPaymentTransactionViewModel($paymentTransaction));
    }

    public function update(UpdatePaymentTransactionRequest $request, PaymentTransaction $paymentTransaction): RedirectResponse
    {
        $this->authorize('update', $paymentTransaction);

        $paymentTransaction->update($request->validated());

        $this->toast('Payment transaction updated successfully.');

        return redirect()->route('dashboard.payment-transactions.show', $paymentTransaction);
    }

    public function destroy(PaymentTransaction $paymentTransaction): RedirectResponse
    {
        $this->authorize('delete', $paymentTransaction);

        $paymentTransaction->delete();

        $this->toast('Payment transaction deleted successfully.', ToastNotificationVariant::Destructive);

        return back(303);
    }
}
