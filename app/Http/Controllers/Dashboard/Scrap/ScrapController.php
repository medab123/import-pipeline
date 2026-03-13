<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\Scrap;

use App\Enums\ToastNotificationVariant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Scrap\StoreScrapRequest;
use App\Http\Requests\Dashboard\Scrap\UpdateScrapRequest;
use App\Http\ViewModels\Dashboard\Scrap\CreateScrapViewModel;
use App\Http\ViewModels\Dashboard\Scrap\EditScrapViewModel;
use App\Http\ViewModels\Dashboard\Scrap\ListScrapViewModel;
use App\Http\ViewModels\Dashboard\Scrap\ShowScrapViewModel;
use App\Models\Scrap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

final class ScrapController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', Scrap::class);

        $query = Scrap::where('organization_uuid', auth()->user()->organization_uuid)
            ->with('dealer:id,name');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('ftp_file_path', 'like', '%'.$request->search.'%')
                    ->orWhere('provider', 'like', '%'.$request->search.'%')
                    ->orWhereHas('dealer', function ($dq) use ($request) {
                        $dq->where('name', 'like', '%'.$request->search.'%');
                    });
            });
        }

        $scraps = $query->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return inertia('Dashboard/Scrap/Index', new ListScrapViewModel($scraps, $request->search));
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', Scrap::class);

        return inertia('Dashboard/Scrap/Create', new CreateScrapViewModel);
    }

    public function store(StoreScrapRequest $request): RedirectResponse
    {
        $this->authorize('create', Scrap::class);

        Scrap::create([
            ...$request->validated(),
            'organization_uuid' => auth()->user()->organization_uuid,
        ]);

        $this->toast('Scrap source created successfully.');

        return redirect()->route('dashboard.scraps.index');
    }

    public function show(Scrap $scrap): InertiaResponse
    {
        $this->authorize('view', $scrap);

        $scrap->load('dealer:id,name');

        return inertia('Dashboard/Scrap/Show', new ShowScrapViewModel($scrap));
    }

    public function edit(Scrap $scrap): InertiaResponse
    {
        $this->authorize('update', $scrap);

        $scrap->load('dealer:id,name');

        return inertia('Dashboard/Scrap/Edit', new EditScrapViewModel($scrap));
    }

    public function update(UpdateScrapRequest $request, Scrap $scrap): RedirectResponse
    {
        $this->authorize('update', $scrap);

        $scrap->update($request->validated());

        $this->toast('Scrap source updated successfully.');

        return redirect()->route('dashboard.scraps.show', $scrap);
    }

    public function destroy(Scrap $scrap): RedirectResponse
    {
        $this->authorize('delete', $scrap);

        $scrap->delete();

        $this->toast('Scrap source deleted successfully.', ToastNotificationVariant::Destructive);

        return back(303);
    }
}
