@php
    $flash = collect([
        'success' => session('success'),
        'error' => session('error'),
        'warning' => session('warning'),
        'info' => session('status'),
    ])->filter()->all();
@endphp

@if ($flash)
    {{-- Rendered inside a Turbo Frame so flash messages survive a
         Turbo Stream redirect without a full reload. --}}
    <turbo-frame id="flash">
        <div class="pointer-events-none fixed inset-x-4 top-4 z-50 mx-auto flex max-w-xl flex-col gap-2">
            @foreach ($flash as $variant => $message)
                <x-ui.alert :variant="$variant" dismissible
                            data-controller="flash"
                            data-flash-timeout-value="6000"
                            class="pointer-events-auto shadow-card-2">
                    {{ $message }}
                </x-ui.alert>
            @endforeach
        </div>
    </turbo-frame>
@else
    <turbo-frame id="flash"></turbo-frame>
@endif
