<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Shared CRUD plumbing for admin resource controllers.
 *
 * Subclasses define $model, $resource (route/route/view stem), $label and
 * either $with (eager loads) or override queries. Delete conflicts from
 * restrictOnDelete foreign keys surface as flash errors, not exceptions.
 */
trait InteractsWithAdminResources
{
    use AuthorizesAdminAccess;

    /**
     * Listing.
     */
    public function index(): View
    {
        $this->authorizeAdmin();

        return view($this->view('index'), [
            'items' => $this->listingQuery()->get(),
        ]);
    }

    /**
     * Create form.
     */
    public function create(): View
    {
        $this->authorizeAdmin();

        return view($this->view('create'), $this->formExtras());
    }

    /**
     * Store.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $this->validateRequest($request);

        $item = $this->model()::create($validated);

        return $this->afterStore($request, $item);
    }

    /**
     * Edit form.
     */
    public function edit(int $id): View
    {
        $this->authorizeAdmin();

        return view($this->view('edit'), array_merge(
            ['item' => $this->findOrFail($id)],
            $this->formExtras(),
        ));
    }

    /**
     * Update.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $this->authorizeAdmin();

        $item = $this->findOrFail($id);
        $validated = $this->validateRequest($request, $item);

        $item->update($validated);

        return $this->afterUpdate($request, $item);
    }

    /**
     * Delete, translating FK restrictions into a flash error.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $this->authorizeAdmin();

        $item = $this->findOrFail($id);

        try {
            $item->delete();
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1451 || str_contains($e->getMessage(), 'FOREIGN KEY constraint failed')) {
                return redirect()
                    ->route($this->route('index'))
                    ->with('error', __('Cannot delete this :label while it is still referenced by other content.', [
                        'label' => $this->label(),
                    ]));
            }

            throw $e;
        }

        return redirect()
            ->route($this->route('index'))
            ->with('success', __(':Label deleted.', ['label' => $this->label()]));
    }

    /**
     * Model class this controller manages.
     */
    abstract protected function model(): string;

    /**
     * Route name stem, e.g. `admin.projects` for index/create/... suffixes.
     */
    abstract protected function resource(): string;

    /**
     * Human label used in flash messages.
     */
    abstract protected function label(): string;

    /**
     * Validation rules; second arg is the item for unique-rule exclusions.
     *
     * @return array<string, mixed>
     */
    abstract protected function rules(Request $request, ?Model $item = null): array;

    /**
     * Listing query — override to eager load or order.
     */
    protected function listingQuery(): Builder
    {
        return $this->model()::query();
    }

    /**
     * Relations to eager load on the edit form (category pickers, ...).
     *
     * @return array<int, string>
     */
    protected function with(): array
    {
        return [];
    }

    /**
     * Extra view data for create/edit forms.
     *
     * @return array<string, mixed>
     */
    protected function formExtras(): array
    {
        return [];
    }

    /**
     * Redirect after store.
     */
    protected function afterStore(Request $request, Model $item): RedirectResponse
    {
        $redirect = redirect()->route($this->route('index'));

        return $request->boolean('save_and_edit')
            ? $redirect->route($this->route('edit'), $item)->with('success', __(':Label created.', ['label' => $this->label()]))
            : $redirect->with('success', __(':Label created.', ['label' => $this->label()]));
    }

    /**
     * Redirect after update.
     */
    protected function afterUpdate(Request $request, Model $item): RedirectResponse
    {
        return redirect()
            ->route($this->route('index'))
            ->with('success', __(':Label updated.', ['label' => $this->label()]));
    }

    /**
     * Validate the request, merging boolean checkboxes that are absent when
     * unchecked.
     *
     * @return array<string, mixed>
     */
    protected function validateRequest(Request $request, ?Model $item = null): array
    {
        $data = $request->validate($this->rules($request, $item));

        foreach (['is_visible', 'featured'] as $flag) {
            if (in_array($flag, $this->booleanFields(), true)) {
                $data[$flag] = $request->boolean($flag);
            }
        }

        return $data;
    }

    /**
     * Boolean fields managed by checkboxes in the form.
     *
     * @return array<int, string>
     */
    protected function booleanFields(): array
    {
        return ['is_visible'];
    }

    /**
     * Find by id or 404, with eager loads applied.
     */
    protected function findOrFail(int $id): Model
    {
        $query = $this->model()::query();

        if ($with = $this->with()) {
            $query->with($with);
        }

        return $query->findOrFail($id);
    }

    /**
     * Route name for an action suffix.
     */
    protected function route(string $action): string
    {
        return $this->resource().'.'.$action;
    }

    /**
     * Admin view name for an action suffix.
     */
    protected function view(string $action): string
    {
        return 'admin.'.$this->viewStem().'.'.$action;
    }

    /**
     * View directory stem; defaults to the resource name after `admin.`.
     */
    protected function viewStem(): string
    {
        return Str::after($this->resource(), 'admin.');
    }
}
