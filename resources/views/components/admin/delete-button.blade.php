@props([
    'route',
    'label' => 'this item',
])

<form method="POST" action="{{ $route }}" data-turbo-confirm="{{ __('Delete :label? This cannot be undone.', ['label' => $label]) }}">
    @csrf
    @method('DELETE')

    <x-ui.button type="submit" variant="danger" size="sm">
        {{ __('Delete') }}
    </x-ui.button>
</form>
