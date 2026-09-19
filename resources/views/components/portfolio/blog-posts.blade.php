@php
    $posts = App\Support\PortfolioContent::posts();
@endphp

<section class="blog-posts">

    <ul class="blog-posts-list">

        @foreach ($posts as $post)
            <li class="blog-post-item">
                <a href="{{ $post['url'] }}">

                    <figure class="blog-banner-box">
                        @php([$bw, $bh] = App\Support\PortfolioContent::mediaDimensions($post['image']))
                        <img src="{{ App\Support\PortfolioContent::mediaUrl($post['image']) }}"
                             @if (App\Support\PortfolioContent::mediaSrcset($post['image'])) srcset="{{ App\Support\PortfolioContent::mediaSrcset($post['image']) }}" @endif
                             alt="{{ $post['alt'] }}"
                             @if ($bw && $bh) width="{{ $bw }}" height="{{ $bh }}" @endif
                             loading="lazy"
                             decoding="async">
                    </figure>

                    <div class="blog-content">

                        <div class="blog-meta">
                            <p class="blog-category">{{ $post['category'] }}</p>

                            <span class="dot"></span>

                            <time datetime="{{ $post['date_iso'] }}">{{ $post['date'] }}</time>
                        </div>

                        <h3 class="h3 blog-item-title">{{ $post['title'] }}</h3>

                        <p class="blog-text">{{ $post['text'] }}</p>

                    </div>

                </a>
            </li>
        @endforeach

    </ul>

</section>
