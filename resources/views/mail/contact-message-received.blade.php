{{--
    New-contact-message notification.

    Every interpolated value is escaped by Blade, so a message containing
    markup is rendered as text. Styles are inline because mail clients do not
    load external stylesheets.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('New contact message') }}</title>
</head>
<body style="margin:0;padding:24px;background:#f4f4f5;font-family:system-ui,-apple-system,'Segoe UI',sans-serif;color:#18181b;">

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;">
    <tr>
        <td style="padding:20px 24px;background:#121212;color:#ffdb70;">
            <p style="margin:0;font-size:13px;letter-spacing:.08em;text-transform:uppercase;">
                {{ $siteName ?? config('app.name') }}
            </p>
            <h1 style="margin:6px 0 0;font-size:19px;color:#ffffff;">
                {{ __('New contact message') }}
            </h1>
        </td>
    </tr>

    <tr>
        <td style="padding:24px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                    <td style="padding:0 0 10px;font-size:13px;color:#71717a;width:120px;">{{ __('From') }}</td>
                    <td style="padding:0 0 10px;font-size:15px;color:#18181b;">
                        {{ $contact->name }}
                        &lt;{{ $contact->email }}&gt;
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 0 10px;font-size:13px;color:#71717a;">{{ __('Subject') }}</td>
                    <td style="padding:0 0 10px;font-size:15px;color:#18181b;">{{ $contact->displaySubject() }}</td>
                </tr>
                <tr>
                    <td style="padding:0 0 10px;font-size:13px;color:#71717a;">{{ __('Received') }}</td>
                    <td style="padding:0 0 10px;font-size:15px;color:#18181b;">
                        {{ $contact->created_at?->format('M j, Y H:i') }}
                    </td>
                </tr>
            </table>

            <div style="margin-top:8px;padding:16px;background:#fafafa;border:1px solid #e4e4e7;border-radius:10px;">
                <p style="margin:0;font-size:15px;line-height:1.6;color:#3f3f46;white-space:pre-line;">{{ $contact->message }}</p>
            </div>

            <p style="margin:20px 0 0;font-size:13px;color:#71717a;">
                {{ __('Reply to this email to answer :name directly.', ['name' => $contact->name]) }}
            </p>

            @if (! empty($inboxUrl))
                <p style="margin:16px 0 0;">
                    <a href="{{ $inboxUrl }}" style="display:inline-block;padding:10px 18px;background:#ffdb70;color:#121212;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none;">
                        {{ __('Open the inbox') }}
                    </a>
                </p>
            @endif
        </td>
    </tr>
</table>

</body>
</html>
