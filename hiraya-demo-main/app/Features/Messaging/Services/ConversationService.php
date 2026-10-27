<?php

namespace App\Features\Messaging\Services;

use App\Features\Messaging\Models\Conversation;
use App\Features\Messaging\Models\Message;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ConversationService
{
    /**
     * Students may only message administrators, registrars, and faculty.
     *
     * @return array<int, string>
     */
    public function allowedStaffRoles(): array
    {
        return [Role::ADMIN, Role::REGISTRAR, Role::FACULTY];
    }

    public function create(Student $student, User $staff, string $subject, string $body, User $sender): Conversation
    {
        if ($student->user_id !== $sender->id) {
            throw ValidationException::withMessages([
                'student' => 'Only the student can start this conversation.',
            ]);
        }

        if (! in_array($staff->role?->slug, $this->allowedStaffRoles(), true)) {
            throw ValidationException::withMessages([
                'staff_id' => 'Students can only message administrators, registrars, or faculty.',
            ]);
        }

        $conversation = Conversation::create([
            'student_id' => $student->id,
            'staff_id' => $staff->id,
            'subject' => $subject,
            'last_message_at' => now(),
        ]);

        $conversation->messages()->create([
            'sender_id' => $sender->id,
            'body' => $body,
        ]);

        return $conversation;
    }

    public function reply(Conversation $conversation, User $sender, string $body): Message
    {
        $isStudent = $conversation->student?->user_id === $sender->id;
        $isStaff = $conversation->staff_id === $sender->id;

        if (! $isStudent && ! $isStaff) {
            throw ValidationException::withMessages([
                'conversation' => 'You are not a participant in this conversation.',
            ]);
        }

        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'body' => $body,
        ]);

        $conversation->forceFill(['last_message_at' => now()])->save();

        return $message;
    }

    public function markRead(Conversation $conversation, User $reader): void
    {
        $conversation->messages()
            ->where('sender_id', '!=', $reader->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function conversationsFor(User $user)
    {
        if ($user->isStudent()) {
            return Conversation::query()
                ->with(['staff:id,name', 'student:id,student_number,first_name,last_name', 'messages' => fn ($query) => $query->latest()->limit(1)])
                ->where('student_id', $user->student?->id)
                ->latest('last_message_at')
                ->get();
        }

        if ($user->isFinance()) {
            return collect();
        }

        return Conversation::query()
            ->with(['staff:id,name', 'student:id,student_number,first_name,last_name', 'messages' => fn ($query) => $query->latest()->limit(1)])
            ->where('staff_id', $user->id)
            ->latest('last_message_at')
            ->get();
    }
}
