@php
    /*
     * Port of the original #PORTFOLIO section.
     *
     * The original compared a button's lower-cased label against each
     * project's `data-category`. We keep the same `data-category` values but
     * read them from an explicit attribute so labels can be translated later.
     */
    $categories = App\Support\PortfolioContent::projectCategories();
    $projects = App\Support\PortfolioContent::projects();
@endphp

<section class="projects" data-controller="portfolio-filter">

    {{-- Desktop: horizontal filter tabs --}}
    <ul class="filter-list">

        @foreach ($categories as $category)
            <li class="filter-item">
                <button class="@if ($loop->first) active @endif"
                        type="button"
                        data-portfolio-filter-target="button"
                        data-category="{{ $category }}"
                        data-action="portfolio-filter#apply">{{ $category }}</button>
            </li>
        @endforeach

    </ul>

    {{-- Mobile: custom select --}}
    <div class="filter-select-box">

        <button class="filter-select"
                type="button"
                data-portfolio-filter-target="select"
                data-action="portfolio-filter#toggleSelect"
                aria-haspopup="listbox"
                aria-expanded="false">

            <div class="select-value" data-portfolio-filter-target="value">{{ __('Select category') }}</div>

            <div class="select-icon">
                <x-portfolio-icon name="chevron-down" />
            </div>

        </button>

        <ul class="select-list">

            @foreach ($categories as $category)
                <li class="select-item">
                    <button type="button"
                            data-category="{{ $category }}"
                            data-action="portfolio-filter#apply">{{ $category }}</button>
                </li>
            @endforeach

        </ul>

    </div>

    <ul class="project-list">

        @foreach ($projects as $project)
            <li class="project-item active"
                data-portfolio-filter-target="item"
                data-category="{{ strtolower($project['category']) }}">
                <a href="#">

                    <figure class="project-img">
                        <div class="project-item-icon-box">
                            <x-portfolio-icon name="eye-outline" />
                        </div>

                        @php([$pw, $ph] = App\Support\PortfolioContent::mediaDimensions($project['image']))
                        <img src="{{ App\Support\PortfolioContent::mediaUrl($project['image']) }}"
                             @if (App\Support\PortfolioContent::mediaSrcset($project['image'])) srcset="{{ App\Support\PortfolioContent::mediaSrcset($project['image']) }}" @endif
                             alt="{{ $project['alt'] }}"
                             @if ($pw && $ph) width="{{ $pw }}" height="{{ $ph }}" @endif
                             loading="lazy"
                             decoding="async">
                    </figure>

                    <h3 class="project-title">{{ $project['title'] }}</h3>

                    <p class="project-category">{{ $project['category'] }}</p>

                </a>
            </li>
        @endforeach

    </ul>

</section>
