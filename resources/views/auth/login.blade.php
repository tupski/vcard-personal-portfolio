<x-layouts.guest :title="__('Sign in')"
                 :heading="__('Sign in')"
                 :description="__('Access the portfolio content management panel.')">
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <x-ui.input
            name="email"
            type="email"
            :label="__('Email address')"
            placeholder="you@example.com"
            autocomplete="username"
            autofocus
            required
        />

        <x-ui.input
            name="password"
            type="password"
            :label="__('Password')"
            placeholder="••••••••"
            autocomplete="current-password"
            required
        />

        <div class="flex items-center justify-between gap-3">
            <x-ui.checkbox name="remember" :label="__('Remember me')" />

            <a href="{{ route('home') }}"
               class="text-fs-7 text-light-gray/60 transition-colors hover:text-brand">
                {{ __('Back to site') }}
            </a>
        </div>

        <x-ui.button type="submit" variant="brand" size="lg" class="w-full">
            {{ __('Sign in') }}
        </x-ui.button>
    </form>
</x-layouts.guest>
