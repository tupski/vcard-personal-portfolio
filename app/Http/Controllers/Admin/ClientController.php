<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminResources;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    use InteractsWithAdminResources;

    protected function model(): string
    {
        return Client::class;
    }

    protected function resource(): string
    {
        return 'admin.clients';
    }

    protected function label(): string
    {
        return __('Client');
    }

    protected function rules(Request $request, ?Model $item = null): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'logo_path' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
