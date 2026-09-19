<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesAdminAccess;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Seo\SeoDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Settings editor.
 *
 * The form fields are exactly the stored setting keys, so validation keys,
 * view field names and DB rows can never drift (see the Sewa Apartemen CMS
 * lessons). Adding a setting in Phase 6 means: add a key + a field + a rule.
 */
class SettingsController extends Controller
{
    use AuthorizesAdminAccess;

    /**
     * Editable settings: key => validation rules.
     *
     * @var array<string, array<int, string>>
     */
    private const EDITABLE = [
        'site.name' => ['required', 'string', 'max:255'],
        'contact.map_embed_url' => ['required', 'string', 'max:2048'],
    ];

    /**
     * Editable keys = site settings + the SEO defaults declared by the SEO
     * layer itself. Declaring them there keeps the form, the validator and
     * the reader in lockstep.
     *
     * @return array<string, array<int, string>>
     */
    private static function rules(): array
    {
        return self::EDITABLE + SeoDefaults::EDITABLE;
    }

    /**
     * Edit form.
     */
    public function edit(): View
    {
        $this->authorizeAdmin();

        $settings = collect(self::rules())
            ->map(fn (array $rules, string $key) => Setting::get($key, ''))
            ->all();

        return view('admin.settings.edit', ['settings' => $settings]);
    }

    /**
     * Persist the declared keys only — anything else posted is ignored.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        // The form posts settings[key] where key itself contains dots
        // (settings[contact.map_embed_url]). PHP keeps those as literal
        // bracket keys, so validator array syntax cannot address them.
        // Validate the flat input map directly and persist declared keys only.
        $input = $request->input('settings', []);

        $validated = [];

        foreach (self::rules() as $key => $keyRules) {
            // Nullable SEO keys: an omitted field validates as null and is
            // stored as an empty string so clearing a value really clears it.
            $value = app('validator')->make(
                ['key' => $input[$key] ?? null],
                ['key' => $keyRules],
            )->validate()['key'];

            $validated[$key] = $value ?? '';
        }

        foreach ($validated as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', __('Settings saved.'));
    }
}
