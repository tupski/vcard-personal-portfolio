@php
    /*
     * Blog detail page.
     *
     * Native to the portfolio: the same `<article>` shell, `.page-header` and
     * `.content-card` surface the rest of the site uses, so the page inherits
     * the Phase 2 typography, spacing and responsive behaviour without a new
     * design system.
     *
     * CONTENT RENDERING CONTRACT
     * The `content` column is PLAIN TEXT (Phase 4 shipped it as a textarea).
     * It is therefore escaped with `{{ }}` and its newlines are turned into
     * real line breaks by CSS (`white-space: pre-line`) — never `{!! !!}`,
     * never nl2br() on raw input. Nothing an author types can become markup,
     * so a stored `<script>` or `<img onerror>` stays inert text.
     */
@endphp

<x-layouts.portfolio :seo="$seo">

    <article class="blog-post">

        <x-portfolio.page-header :title="$post['title']" />

        <nav class="blog-breadcrumb" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('blog') }}">{{ __('Blog') }}</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">{{ $post['title'] }}</span>
        </nav>

        <div class="blog-post-meta">
            <p class="blog-category">{{ $post['category'] }}</p>

            <span class="dot" aria-hidden="true"></span>

            <time datetime="{{ $post['date_iso'] }}">{{ $post['date'] }}</time>
        </div>

        @if ($post['image'])
            <figure class="blog-post-banner">
                {{-- The featured image is the page's largest paint, so it is
                     loaded eagerly rather than lazily. --}}
                <img src="{{ App\Support\PortfolioContent::mediaUrl($post['image']) }}"
                     alt="{{ $post['alt'] }}"
                     width="1200"
                     height="675">
            </figure>
        @endif

        @if ($post['excerpt'])
            <p class="blog-post-excerpt">{{ $post['excerpt'] }}</p>
        @endif

        {{-- Escaped plain text; `white-space: pre-line` preserves the author's
             line breaks and blank lines without any HTML parsing. --}}
        <div class="blog-post-body">{{ $post['body'] }}</div>

        @if ($related)
            <section class="blog-related">
                <h3 class="h3">{{ __('More from the blog') }}</h3>

                <ul class="blog-related-list">
                    @foreach ($related as $item)
                        <li class="blog-related-item">
                            <a href="{{ $item['url'] }}">
                                <span class="blog-related-title">{{ $item['title'] }}</span>
                                <span class="blog-related-meta">
                                    {{ $item['category'] }}
                                    ·
                                    <time datetime="{{ $item['date_iso'] }}">{{ $item['date'] }}</time>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <p class="blog-back">
            <a href="{{ route('blog') }}">
                <span class="vcard-icon" role="img" aria-hidden="true" data-icon="chevron-down"><svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="48" d="M112 184l144 144 144-144" class="ionicon-fill-none"/></svg></span>
                {{ __('Back to all posts') }}
            </a>
        </p>

    </article>

</x-layouts.portfolio>
