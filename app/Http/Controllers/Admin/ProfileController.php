<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesAdminAccess;
use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use AuthorizesAdminAccess;

    /**
     * Edit the single profile row.
     */
    public function edit(): View
    {
        $this->authorizeAdmin();

        return view('admin.profile.edit', [
            'profile' => Profile::query()->firstOrFail(),
        ]);
    }

    /**
     * Update the profile.
     *
     * The phone column pair (display value vs tel: href) is preserved
     * verbatim: what the admin types is what is stored and rendered.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'avatar_path' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'phone_href' => ['required', 'string', 'max:255'],
            'birthday' => ['required', 'date'],
            'location' => ['required', 'string', 'max:255'],
            'about' => ['required', 'string'],
        ]);

        Profile::query()->firstOrFail()->update($validated);

        return redirect()
            ->route('admin.profile.edit')
            ->with('success', __('Profile updated.'));
    }
}
