@props(['seo'])

{{--
    JSON-LD graphs for the current page.

    Rendered at the end of <body> rather than in <head>: Turbo Drive swaps the
    body on navigation, so the graph always reflects the page in the DOM, and
    script elements inserted into <head> are not re-evaluated by the merger.

    Encoding uses @json with the JSON_UNESCAPED_* flags off (default escaping
    is on), so quotes, apostrophes, ampersands, angle brackets and Unicode in
    database content can never break out of the script or produce invalid JSON.
--}}
@foreach ($seo->schemas as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endforeach
