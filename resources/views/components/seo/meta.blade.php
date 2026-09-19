@props(['seo'])

{{--
    Single source of head metadata for public pages.

    Every tag is derived from the SeoData value object; no page template
    builds metadata itself. Turbo Drive merges <head> on navigation, so these
    elements update in place on every visit — the <title>, canonical, robots,
    description and social tags all track the page the browser is actually on.
--}}
<title>{{ $seo->title }}{{ $seo->ogSiteName ? ' - '.$seo->ogSiteName : '' }}</title>

@if ($seo->description)
    <meta name="description" content="{{ $seo->description }}">
@endif

<meta name="robots" content="{{ $seo->robots }}">

@if ($seo->canonical)
    <link rel="canonical" href="{{ $seo->canonical }}">
@endif

{{-- Open Graph --}}
<meta property="og:type" content="{{ $seo->ogType }}">
<meta property="og:title" content="{{ $seo->title }}">
@if ($seo->ogSiteName)
    <meta property="og:site_name" content="{{ $seo->ogSiteName }}">
@endif
@if ($seo->description)
    <meta property="og:description" content="{{ $seo->description }}">
@endif
@if ($seo->canonical)
    <meta property="og:url" content="{{ $seo->canonical }}">
@endif
@if ($seo->ogImage)
    <meta property="og:image" content="{{ $seo->ogImage }}">
    @if ($seo->ogImageAlt)
        <meta property="og:image:alt" content="{{ $seo->ogImageAlt }}">
    @endif
    @if ($seo->ogImageWidth)
        <meta property="og:image:width" content="{{ $seo->ogImageWidth }}">
    @endif
    @if ($seo->ogImageHeight)
        <meta property="og:image:height" content="{{ $seo->ogImageHeight }}">
    @endif
@endif
@if ($seo->locale)
    <meta property="og:locale" content="{{ $seo->locale }}">
@endif

{{-- Twitter / X card --}}
<meta name="twitter:card" content="{{ $seo->twitterCard() }}">
<meta name="twitter:title" content="{{ $seo->title }}">
@if ($seo->description)
    <meta name="twitter:description" content="{{ $seo->description }}">
@endif
@if ($seo->ogImage)
    <meta name="twitter:image" content="{{ $seo->ogImage }}">
    @if ($seo->ogImageAlt)
        <meta name="twitter:image:alt" content="{{ $seo->ogImageAlt }}">
    @endif
@endif
