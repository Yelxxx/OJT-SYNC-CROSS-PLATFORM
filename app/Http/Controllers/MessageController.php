<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Message;
use App\Models\Student;
use App\Models\User;
use App\Notifications\NewDirectMessageNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        // 1. Determine legitimate contacts the user can message based on their role
        $contacts = $this->resolveAvailableContacts($user);

        // 2. Determine active contact
        $selectedUserId = $request->integer('with');
        $activeContact = null;

        if ($selectedUserId) {
            $activeContact = $contacts->firstWhere('id', $selectedUserId);
        }

        if (! $activeContact && $contacts->isNotEmpty()) {
            $activeContact = $contacts->first();
        }

        // 3. Load conversation messages if an active contact exists
        $messages = collect();
        if ($activeContact) {
            // Mark incoming unread messages as read
            Message::where('sender_id', $activeContact->id)
                ->where('recipient_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $messages = Message::query()
                ->where(function ($q) use ($user, $activeContact) {
                    $q->where('sender_id', $user->id)->where('recipient_id', $activeContact->id);
                })
                ->orWhere(function ($q) use ($user, $activeContact) {
                    $q->where('sender_id', $activeContact->id)->where('recipient_id', $user->id);
                })
                ->orderBy('created_at', 'asc')
                ->get();
        }

        return view('messages.index', [
            'contacts' => $contacts,
            'activeContact' => $activeContact,
            'messages' => $messages,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $recipient = User::findOrFail($validated['recipient_id']);

        // Prevent unauthorized communication
        $contacts = $this->resolveAvailableContacts($user);
        abort_unless($contacts->contains('id', $recipient->id), 403, 'You are not authorized to message this user.');

        $studentId = null;
        if ($user->isStudent()) {
            $studentId = $user->studentProfile?->id;
        } elseif ($recipient->isStudent()) {
            $studentId = $recipient->studentProfile?->id;
        }

        $message = Message::create([
            'sender_id' => $user->id,
            'recipient_id' => $recipient->id,
            'student_id' => $studentId,
            'body' => $validated['body'],
        ]);

        // Send alert
        $recipient->notify(new NewDirectMessageNotification($message));

        return redirect()->route('messages.index', ['with' => $recipient->id]);
    }

    private function resolveAvailableContacts(User $user)
    {
        $contactUsers = collect();

        if ($user->isStudent()) {
            $student = $user->studentProfile;

            // Host Company HR contact
            if ($student && $student->company_id) {
                $hrUsers = User::where('company_id', $student->company_id)->get();
                foreach ($hrUsers as $hr) {
                    $hr->custom_role_label = 'Host Company HR (' . ($student->company->name ?? 'Company') . ')';
                    $contactUsers->push($hr);
                }
            }

            // University Coordinator contact
            if ($student && $student->coordinator_id) {
                $coordinator = User::find($student->coordinator_id);
                if ($coordinator) {
                    $coordinator->custom_role_label = 'OJT Practicum Coordinator';
                    $contactUsers->push($coordinator);
                }
            }
        } elseif ($user->isHr()) {
            $companyId = $user->company_id;

            // Hired Interns
            $interns = Student::where('company_id', $companyId)
                ->with('user', 'coordinator')
                ->get();

            foreach ($interns as $intern) {
                if ($intern->user) {
                    $u = $intern->user;
                    $u->custom_role_label = 'Intern (' . ($intern->program ?? 'Student') . ')';
                    $contactUsers->push($u);
                }

                // Student's university coordinator
                if ($intern->coordinator) {
                    $c = $intern->coordinator;
                    $c->custom_role_label = 'Coordinator (' . ($intern->user->name ?? 'Student') . '\'s Advisor)';
                    $contactUsers->push($c);
                }
            }
        } elseif ($user->isCoordinator()) {
            // Supervised students
            $students = $user->supervisedStudents()->with('user', 'company')->get();

            foreach ($students as $student) {
                if ($student->user) {
                    $u = $student->user;
                    $u->custom_role_label = 'Advisee (' . ($student->student_no ?? 'Student') . ')';
                    $contactUsers->push($u);
                }

                // Company HR representatives where student is placed
                if ($student->company_id) {
                    $hrUsers = User::where('company_id', $student->company_id)->get();
                    foreach ($hrUsers as $hr) {
                        $hr->custom_role_label = 'HR (' . ($student->company->name ?? 'Company') . ')';
                        $contactUsers->push($hr);
                    }
                }
            }
        }

        // Deduplicate contacts and attach unread count
        return $contactUsers->unique('id')->values()->map(function ($contact) use ($user) {
            $contact->unread_count = Message::where('sender_id', $contact->id)
                ->where('recipient_id', $user->id)
                ->whereNull('read_at')
                ->count();
            return $contact;
        });
    }
}
