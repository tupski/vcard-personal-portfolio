@php
    $testimonials = App\Support\PortfolioContent::testimonials();
@endphp

{{-- Wraps the list and the modal so they share one Stimulus controller. --}}
<div data-controller="testimonials">

    <section class="testimonials">

        <h3 class="h3 testimonials-title">{{ __('Testimonials') }}</h3>

        <ul class="testimonials-list has-scrollbar">

            @foreach ($testimonials as $testimonial)
                <li class="testimonials-item">
                    <div class="content-card"
                         role="button"
                         tabindex="0"
                         data-testimonials-target="card"
                         data-action="click->testimonials#open keydown.enter->testimonials#open keydown.space->testimonials#open">

                        <figure class="testimonials-avatar-box">
                            @php([$cardWidth, $cardHeight] = App\Support\PortfolioContent::mediaDimensions($testimonial['avatar']))
                            <img src="{{ App\Support\PortfolioContent::mediaThumbUrl($testimonial['avatar']) }}"
                                 alt="{{ $testimonial['name'] }}"
                                 width="{{ $cardWidth ?? 60 }}"
                                 height="{{ $cardHeight ?? 60 }}"
                                 data-testimonials-avatar>
                        </figure>

                        <h4 class="h4 testimonials-item-title" data-testimonials-title>{{ $testimonial['name'] }}</h4>

                        <div class="testimonials-text" data-testimonials-text>
                            <p>{{ $testimonial['text'] }}</p>
                        </div>

                        {{-- Consumed by the modal; mirrors the original modal date. --}}
                        <time datetime="{{ $testimonial['date_iso'] }}"
                              class="sr-only"
                              data-testimonials-date>{{ $testimonial['date'] }}</time>

                    </div>
                </li>
            @endforeach

        </ul>

    </section>

    {{-- testimonials modal --}}
    <div class="modal-container" data-testimonials-target="modal">

        <div class="overlay"
             data-testimonials-target="overlay"
             data-action="click->testimonials#close"></div>

        <section class="testimonials-modal"
                 role="dialog"
                 aria-modal="true"
                 aria-label="{{ __('Testimonial') }}">

            <button class="modal-close-btn"
                    type="button"
                    data-testimonials-target="close"
                    data-action="testimonials#close"
                    aria-label="{{ __('Close') }}">
                <x-portfolio-icon name="close-outline" />
            </button>

            <div class="modal-img-wrapper">
                <figure class="modal-avatar-box">
                    @php([$modalWidth, $modalHeight] = App\Support\PortfolioContent::mediaDimensions($testimonials[0]['avatar']))
                    <img src="{{ App\Support\PortfolioContent::mediaThumbUrl($testimonials[0]['avatar']) }}"
                         alt="{{ $testimonials[0]['name'] }}"
                         width="{{ $modalWidth ?? 80 }}"
                         height="{{ $modalHeight ?? 80 }}"
                         data-testimonials-target="img">
                </figure>

                {{-- Decorative quote glyph. The file is 34x23; declaring it lets the
                     browser reserve the space. It carries no meaning beyond decoration,
                     and the surrounding text already conveys the testimonial. --}}
                <img src="{{ asset('assets/images/icon-quote.svg') }}"
                     alt=""
                     aria-hidden="true"
                     width="34"
                     height="23">
            </div>

            <div class="modal-content">

                <h4 class="h3 modal-title" data-testimonials-target="title">{{ $testimonials[0]['name'] }}</h4>

                <time datetime="{{ $testimonials[0]['date_iso'] }}"
                      data-testimonials-target="date">{{ $testimonials[0]['date'] }}</time>

                <div data-testimonials-target="text">
                    <p>{{ $testimonials[0]['text'] }}</p>
                </div>

            </div>

        </section>

    </div>

</div>
