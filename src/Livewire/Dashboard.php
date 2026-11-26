<?php

namespace Platform\Gathering\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Platform\Gathering\Enums\MeetingStatus;
use Platform\Gathering\Models\Meeting;

/**
 * Übersicht aller Meetings des aktuellen Teams (anstehend + vergangen) mit „Neues Meeting".
 * Ein Meeting ist das Ding — hier host-los angelegt, der Host (worum's geht) folgt optional.
 */
class Dashboard extends Component
{
    public bool $showCreate = false;
    public array $form = ['title' => '', 'scheduled_at' => null, 'duration_minutes' => null, 'location' => null];

    public function openCreate(): void
    {
        $this->form = ['title' => '', 'scheduled_at' => null, 'duration_minutes' => null, 'location' => null];
        $this->resetValidation();
        $this->showCreate = true;
    }

    public function createMeeting()
    {
        $user = Auth::user();
        abort_unless($user && $user->currentTeam, 403);

        $this->validate([
            'form.title' => 'required|string|max:255',
            'form.scheduled_at' => 'nullable|date',
            'form.duration_minutes' => 'nullable|integer|min:1|max:1440',
            'form.location' => 'nullable|string|max:255',
        ]);

        $meeting = Meeting::create([
            'team_id' => $user->currentTeam->id,
            'title' => trim($this->form['title']),
            'scheduled_at' => $this->form['scheduled_at'] ?: null,
            'duration_minutes' => $this->form['duration_minutes'] ?: null,
            'location' => $this->form['location'] ?: null,
            'status' => MeetingStatus::PLANNED->value,
            'created_by' => $user->id,
        ]);

        $this->showCreate = false;

        return $this->redirect(route('gathering.meetings.show', $meeting), navigate: true);
    }

    public function render()
    {
        $user = Auth::user();
        $teamId = $user?->currentTeam?->id;

        $base = Meeting::query()
            ->where('team_id', $teamId)
            ->with('host') // Herkunft-Chip ohne N+1
            ->withCount(['agendaItems', 'decisions', 'attendees']);

        $upcoming = (clone $base)->upcoming()
            ->orderBy('scheduled_at')
            ->get();

        $recent = (clone $base)
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<', now())
                    ->orWhereIn('status', [MeetingStatus::HELD->value, MeetingStatus::CANCELLED->value]);
            })
            ->orderByRaw('COALESCE(scheduled_at, created_at) DESC')
            ->limit(50)
            ->get();

        return view('gathering::livewire.dashboard', [
            'upcoming' => $upcoming,
            'recent' => $recent,
            'total' => (clone $base)->count(),
        ])->layout('platform::layouts.app');
    }
}
