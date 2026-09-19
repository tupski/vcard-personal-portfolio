<?php

namespace App\Http\Controllers\Admin\Concerns;

/**
 * Authorization boundary for the admin panel.
 *
 * The portfolio has a single admin role today, so this trait currently only
 * checks authentication (already enforced by the `auth` middleware on the
 * admin route group). Centralising the check here means a role/permission
 * layer can be introduced later by changing this one method instead of every
 * controller.
 */
trait AuthorizesAdminAccess
{
    /**
     * Authorize the current request against the admin area.
     */
    protected function authorizeAdmin(): void
    {
        abort_unless(auth()->check(), 403);
    }
}
