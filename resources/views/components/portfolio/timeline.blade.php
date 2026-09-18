@props([
    'title',
    'items',
    'icon' => 'book-outline',
])

<section class="timeline">

    <div class="title-wrapper">
        <div class="icon-box">
            <x-icon :name="$icon" />
        </div>

        <h3 class="h3">{{ $title }}</h3>
    </div>

    <ol class="timeline-list">

        @foreach ($items as $item)
            <li class="timeline-item">

                <h4 class="h4 timeline-item-title">{{ $item['title'] }}</h4>

                <span>{{ $item['period'] }}</span>

                <p class="timeline-text">{{ $item['text'] }}</p>

            </li>
        @endforeach

    </ol>

</section>
