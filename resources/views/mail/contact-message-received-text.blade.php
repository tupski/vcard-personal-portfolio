{{ __('New contact message') }}

{{ __('From') }}: {{ $contact->name }} <{{ $contact->email }}>
{{ __('Subject') }}: {{ $contact->displaySubject() }}
{{ __('Received') }}: {{ $contact->created_at?->format('M j, Y H:i') }}

{{ $contact->message }}

@if (! empty($inboxUrl))
{{ __('Open the inbox') }}: {{ $inboxUrl }}
@endif
