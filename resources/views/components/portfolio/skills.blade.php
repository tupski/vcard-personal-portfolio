@php
    $skills = App\Support\PortfolioContent::skills();
@endphp

<section class="skill">

    <h3 class="h3 skills-title">{{ __('My skills') }}</h3>

    <ul class="skills-list content-card">

        @foreach ($skills as $skill)
            <li class="skills-item">

                <div class="title-wrapper">
                    <h5 class="h5">{{ $skill['title'] }}</h5>
                    <data value="{{ $skill['percent'] }}">{{ $skill['percent'] }}%</data>
                </div>

                <div class="skill-progress-bg"
                     role="progressbar"
                     aria-label="{{ $skill['title'] }}"
                     aria-valuenow="{{ $skill['percent'] }}"
                     aria-valuemin="0"
                     aria-valuemax="100">
                    <div class="skill-progress-fill" style="width: {{ $skill['percent'] }}%;"></div>
                </div>

            </li>
        @endforeach

    </ul>

</section>
