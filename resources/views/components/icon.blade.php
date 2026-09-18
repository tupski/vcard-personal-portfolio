@props([
    'name',
    'label' => null,
])

@php
    /*
     * Icon sources inlined from `ionicons@5.5.2`, the exact version the
     * original template loaded from unpkg. The markup and classes are
     * verbatim so the shadow-DOM styling reproduced in frontend.css
     * (fill/stroke/--ionicon-stroke-width) behaves identically.
     *
     * Keys mirror the original `name="…"` attributes on <ion-icon>.
     */
    $icons = [
        'mail-outline' => '<svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><rect x="48" y="96" width="416" height="320" rx="40" ry="40" stroke-linecap="round" stroke-linejoin="round" class="ionicon-fill-none ionicon-stroke-width"/><path stroke-linecap="round" stroke-linejoin="round" d="M112 160l144 112 144-112" class="ionicon-fill-none ionicon-stroke-width"/></svg>',
        'phone-portrait-outline' => '<svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><rect x="128" y="16" width="256" height="480" rx="48" ry="48" stroke-linecap="round" stroke-linejoin="round" class="ionicon-fill-none ionicon-stroke-width"/><path d="M176 16h24a8 8 0 018 8h0a16 16 0 0016 16h64a16 16 0 0016-16h0a8 8 0 018-8h24" stroke-linecap="round" stroke-linejoin="round" class="ionicon-fill-none ionicon-stroke-width"/></svg>',
        'calendar-outline' => '<svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><rect stroke-linejoin="round" x="48" y="80" width="416" height="384" rx="48" class="ionicon-fill-none ionicon-stroke-width"/><circle cx="296" cy="232" r="24"/><circle cx="376" cy="232" r="24"/><circle cx="296" cy="312" r="24"/><circle cx="376" cy="312" r="24"/><circle cx="136" cy="312" r="24"/><circle cx="216" cy="312" r="24"/><circle cx="136" cy="392" r="24"/><circle cx="216" cy="392" r="24"/><circle cx="296" cy="392" r="24"/><path stroke-linejoin="round" stroke-linecap="round" d="M128 48v32M384 48v32" class="ionicon-fill-none ionicon-stroke-width"/><path stroke-linejoin="round" d="M464 160H48" class="ionicon-fill-none ionicon-stroke-width"/></svg>',
        'location-outline' => '<svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M256 48c-79.5 0-144 61.39-144 137 0 87 96 224.87 131.25 272.49a15.77 15.77 0 0025.5 0C304 409.89 400 272.07 400 185c0-75.61-64.5-137-144-137z" stroke-linecap="round" stroke-linejoin="round" class="ionicon-fill-none ionicon-stroke-width"/><circle cx="256" cy="192" r="48" stroke-linecap="round" stroke-linejoin="round" class="ionicon-fill-none ionicon-stroke-width"/></svg>',
        'chevron-down' => '<svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="48" d="M112 184l144 144 144-144" class="ionicon-fill-none"/></svg>',
        'logo-facebook' => '<svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M480 257.35c0-123.7-100.3-224-224-224s-224 100.3-224 224c0 111.8 81.9 204.47 189 221.29V322.12h-56.89v-64.77H221V208c0-56.13 33.45-87.16 84.61-87.16 24.51 0 50.15 4.38 50.15 4.38v55.13H327.5c-27.81 0-36.51 17.26-36.51 35v42h62.12l-9.92 64.77H291v156.54c107.1-16.81 189-109.48 189-221.31z" fill-rule="evenodd"/></svg>',
        'logo-twitter' => '<svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M496 109.5a201.8 201.8 0 01-56.55 15.3 97.51 97.51 0 0043.33-53.6 197.74 197.74 0 01-62.56 23.5A99.14 99.14 0 00348.31 64c-54.42 0-98.46 43.4-98.46 96.9a93.21 93.21 0 002.54 22.1 280.7 280.7 0 01-203-101.3A95.69 95.69 0 0036 130.4c0 33.6 17.53 63.3 44 80.7A97.5 97.5 0 0135.22 199v1.2c0 47 34 86.1 79 95a100.76 100.76 0 01-25.94 3.4 94.38 94.38 0 01-18.51-1.8c12.51 38.5 48.92 66.5 92.05 67.3A199.59 199.59 0 0139.5 405.6a203 203 0 01-23.5-1.4A278.68 278.68 0 00166.74 448c181.36 0 280.44-147.7 280.44-275.8 0-4.2-.11-8.4-.31-12.5A198.48 198.48 0 00496 109.5z"/></svg>',
        'logo-instagram' => '<svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M349.33 69.33a93.62 93.62 0 0193.34 93.34v186.66a93.62 93.62 0 01-93.34 93.34H162.67a93.62 93.62 0 01-93.34-93.34V162.67a93.62 93.62 0 0193.34-93.34h186.66m0-37.33H162.67C90.8 32 32 90.8 32 162.67v186.66C32 421.2 90.8 480 162.67 480h186.66C421.2 480 480 421.2 480 349.33V162.67C480 90.8 421.2 32 349.33 32z"/><path d="M377.33 162.67a28 28 0 1128-28 27.94 27.94 0 01-28 28zM256 181.33A74.67 74.67 0 11181.33 256 74.75 74.75 0 01256 181.33m0-37.33a112 112 0 10112 112 112 112 0 00-112-112z"/></svg>',
        'book-outline' => '<svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M256 160c16-63.16 76.43-95.41 208-96a15.94 15.94 0 0116 16v288a16 16 0 01-16 16c-128 0-177.45 25.81-208 64-30.37-38-80-64-208-64-9.88 0-16-8.05-16-17.93V80a15.94 15.94 0 0116-16c131.57.59 192 32.84 208 96zM256 160v288" stroke-linecap="round" stroke-linejoin="round" class="ionicon-fill-none ionicon-stroke-width"/></svg>',
        'eye-outline' => '<svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M255.66 112c-77.94 0-157.89 45.11-220.83 135.33a16 16 0 00-.27 17.77C82.92 340.8 161.8 400 255.66 400c92.84 0 173.34-59.38 221.79-135.25a16.14 16.14 0 000-17.47C428.89 172.28 347.8 112 255.66 112z" stroke-linecap="round" stroke-linejoin="round" class="ionicon-fill-none ionicon-stroke-width"/><circle cx="256" cy="256" r="80" stroke-miterlimit="10" class="ionicon-fill-none ionicon-stroke-width"/></svg>',
        'close-outline' => '<svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path stroke-linecap="round" stroke-linejoin="round" d="M368 368L144 144M368 144L144 368" class="ionicon-fill-none ionicon-stroke-width"/></svg>',
        'paper-plane' => '<svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M473 39.05a24 24 0 00-25.5-5.46L47.47 185h-.08a24 24 0 001 45.16l.41.13 137.3 58.63a16 16 0 0015.54-3.59L422 80a7.07 7.07 0 0110 10L226.66 310.26a16 16 0 00-3.59 15.54l58.65 137.38c.06.2.12.38.19.57 3.2 9.27 11.3 15.81 21.09 16.25h1a24.63 24.63 0 0023-15.46L478.39 64.62A24 24 0 00473 39.05z"/></svg>',
    ];

    $svg = $icons[$name] ?? null;
@endphp

@if ($svg)
    <span {{ $attributes->class(['vcard-icon']) }}
          role="img"
          @if ($label) aria-label="{{ $label }}" @else aria-hidden="true" @endif
          data-icon="{{ $name }}">{!! $svg !!}</span>
@endif
