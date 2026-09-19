<x-layouts.portfolio :seo="$seo">

    <article class="resume">

        <x-portfolio.page-header :title="__('Resume')" />

        <x-portfolio.timeline :title="__('Education')" :items="App\Support\PortfolioContent::education()" />

        <x-portfolio.timeline :title="__('Experience')" :items="App\Support\PortfolioContent::experience()" />

        <x-portfolio.skills />

    </article>

</x-layouts.portfolio>
