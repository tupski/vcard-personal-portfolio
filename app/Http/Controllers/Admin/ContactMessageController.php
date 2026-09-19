<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesAdminAccess;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Contact message inbox. Email sending arrives in Phase 7; this handles the
 * stored messages only.
 */
class ContactMessageController extends Controller
{
    use AuthorizesAdminAccess;

    /**
     * Inbox listing.
     */
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $messages = ContactMessage::query()
            ->when($request->string('filter')->toString() === 'unread', fn ($q) => $q->unread())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.contact-messages.index', [
            'messages' => $messages,
            'unreadCount' => ContactMessage::query()->unread()->count(),
        ]);
    }

    /**
     * Read a message (marks it read).
     */
    public function show(ContactMessage $message): View
    {
        $this->authorizeAdmin();

        $message->markAsRead();

        return view('admin.contact-messages.show', ['message' => $message]);
    }

    /**
     * Toggle read state from the list.
     */
    public function update(Request $request, ContactMessage $message): RedirectResponse
    {
        $this->authorizeAdmin();

        $message->update(['is_read' => $request->boolean('is_read')]);

        return redirect()
            ->route('admin.contact-messages.index')
            ->with('success', $message->is_read
                ? __('Message marked as read.')
                : __('Message marked as unread.'));
    }

    /**
     * Delete a message.
     */
    public function destroy(ContactMessage $message): RedirectResponse
    {
        $this->authorizeAdmin();

        $message->delete();

        return redirect()
            ->route('admin.contact-messages.index')
            ->with('success', __('Message deleted.'));
    }
}
