<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesAdminAccess;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Support\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MediaController extends Controller
{
    use AuthorizesAdminAccess;

    public function __construct(private readonly MediaService $media) {}

    /**
     * Media library grid.
     */
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $media = Media::query()
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return view('admin.media.index', ['media' => $media]);
    }

    /**
     * Store an upload.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $this->media->upload(
            $request->file('file'),
            $validated['alt_text'] ?? null,
            $validated['title'] ?? null,
        );

        return redirect()
            ->route('admin.media.index')
            ->with('success', __('Image uploaded.'));
    }

    /**
     * Edit metadata (alt text / title).
     */
    public function edit(Media $medium): View
    {
        $this->authorizeAdmin();

        return view('admin.media.edit', ['medium' => $medium]);
    }

    /**
     * Update metadata.
     */
    public function update(Request $request, Media $medium): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'alt_text' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $medium->update($validated);

        return redirect()
            ->route('admin.media.index')
            ->with('success', __('Media updated.'));
    }

    /**
     * Delete a medium and exactly its files.
     */
    public function destroy(Media $medium): RedirectResponse
    {
        $this->authorizeAdmin();

        $this->media->delete($medium);

        return redirect()
            ->route('admin.media.index')
            ->with('success', __('Media deleted.'));
    }
}
