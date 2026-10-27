<?php

namespace App\Features\Messaging\Http\Controllers;

use App\Features\Messaging\Models\Conversation;
use App\Features\Messaging\Services\ConversationService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function index(Request $request, ConversationService $conversationService): Response
    {
        $user = $request->user();

        return Inertia::render('Deferred/Messaging/Index', [
            'conversations' => $conversationService->conversationsFor($user),
            'staff' => $user->isStudent()
                ? User::query()
                    ->with('role:id,name,slug')
                    ->whereHas('role', fn ($query) => $query->whereIn('slug', $conversationService->allowedStaffRoles()))
                    ->orderBy('name')
                    ->get(['id', 'name', 'role_id'])
                : [],
        ]);
    }

    public function show(Request $request, Conversation $conversation, ConversationService $conversationService): Response
    {
        $this->authorizeParticipant($request->user(), $conversation);
        $conversationService->markRead($conversation, $request->user());

        $conversation->load(['student.user:id,name', 'staff:id,name', 'messages.sender:id,name']);

        return Inertia::render('Deferred/Messaging/Show', [
            'conversation' => $conversation,
            'canReply' => true,
        ]);
    }

    public function store(Request $request, ConversationService $conversationService): RedirectResponse
    {
        if (! $request->user()->isStudent()) {
            abort(403, 'Only students can start a support conversation.');
        }

        $validated = $request->validate([
            'staff_id' => ['required', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $student = $request->user()->student;

        if (! $student) {
            abort(403, 'This account is not linked to a student record.');
        }

        $staff = User::query()->with('role')->findOrFail($validated['staff_id']);
        $conversation = $conversationService->create(
            $student,
            $staff,
            $validated['subject'],
            $validated['body'],
            $request->user(),
        );

        return redirect()->route('deferred.messages.show', $conversation)->with('success', 'Message sent.');
    }

    public function reply(Request $request, Conversation $conversation, ConversationService $conversationService): RedirectResponse
    {
        $this->authorizeParticipant($request->user(), $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $conversationService->reply($conversation, $request->user(), $validated['body']);

        return back()->with('success', 'Reply sent.');
    }

    private function authorizeParticipant(User $user, Conversation $conversation): void
    {
        if ($conversation->student?->user_id === $user->id || $conversation->staff_id === $user->id) {
            return;
        }

        abort(403, 'You are not a participant in this conversation.');
    }
}
