@php
    $paragraphs = App\Support\PortfolioContent::about();
@endphp

<section class="about-text">
    @foreach ($paragraphs as $paragraph)
        <p>{{ $paragraph }}</p>
    @endforeach
</section>
