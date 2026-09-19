<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminResources;
use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    use InteractsWithAdminResources;

    protected function model(): string
    {
        return Testimonial::class;
    }

    protected function resource(): string
    {
        return 'admin.testimonials';
    }

    protected function label(): string
    {
        return __('Testimonial');
    }

    protected function rules(Request $request, ?Model $item = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'avatar_path' => ['required', 'string', 'max:255'],
            'testimonial_date' => ['required', 'date'],
            'display_date' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
