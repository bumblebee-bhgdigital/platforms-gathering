<?php

namespace Platform\Gathering\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Platform\Gathering\Enums\MeetingStatus;
use Platform\Gathering\Models\Meeting;

/**
 * Einbettbarer Block „Meetings" für einen Host-Kontext (Projekt, Person, Gremium …).
 *
 * Ein Host-Modul droppt ihn auf seine Detailseite — wie Anhang hochladen oder Zeit stempeln:
 *   <livewire:gathering.context-meetings host-type="project" :host-id="$project->id" :host-label="$project->name" />
 *
 * Zeigt die Meeting-Liste dieses Hosts (= die „Serie" am Kontext) und legt im Kontext neue an.
 * Rendert inline (kein Seiten-Layout).
 */
class ContextMeetings extends Component
{
    public string $hostType;
    public int $hostId;
    public ?string $hostLabel = null;
    public bool $compact = false;

    public bool $showCreate = false;
    public array $form = ['title' => '', 'scheduled_at' => null, 'duration_minutes' => null, 'location' => null];

    public function mount(string $hostType, int $hostId, ?string $hostLabel = null, bool $compact = false): void
    {
        $this->hostType = $hostType;
        $this->hostId = $hostId;
        $this->hostLabel = $hostLabel;
        $this->compact = $compact;
    }

    public function openCreate(): void
    {
        $this->form = ['title' => '', 'scheduled_at' => null, 'duration_minutes' => null, 'location' => null];
        $this->resetValidation();
        $this->showCreate = true;
    }

    /** „Nächstes aus dem letzten": Titel/Teilnehmer/Agenda aus dem jüngsten Meeting dieses Hosts übernehmen. */
    public function createFromLast()
    {
        $last = Meeting::query()->forHost($this->hostType, $this->hostId)
            ->orderByRaw('COALESCE(scheduled_at, created_at) DESC')
            ->first();

        if (! $last) {
            $this->openCreate();

            return null;
        }

        $meeting = $this->newMeeting($last->title);

        foreach ($last->agendaItems()->get() as $item) {
            $meeting->agendaItems()->create([
                'position' => $item->position,
                'title' => $item->title,
                'description' => $item->description,
                'owner_user_id' => $item->owner_user_id,
                'duration_minutes' => $item->duration_minutes,
                'created_by' => Auth::id(),
            ]);
        }
        foreach ($last->attendees()->get() as $att) {
            $meeting->attendees()->create([
                'user_id' => $att->user_id,
                'agent_id' => $att->agent_id,
                'role' => $att->role,
                'attended' => false,
            ]);
        }
        // Offene Beschlüsse wandern mit (als offen, neu referenziert auf dieses Meeting).
        foreach ($last->decisions()->where('status', 'open')->get() as $dec) {
            $meeting->decisions()->create([
                'text' => $dec->text,
                'status' => 'open',
                'owner_user_id' => $dec->owner_user_id,
                'owner_agent_id' => $dec->owner_agent_id,
                'due_date' => $dec->due_date,
                'created_by' => Auth::id(),
            ]);
        }

        return $this->redirect(route('gathering.meetings.show', $meeting), navigate: true);
    }

    public function createMeeting()
    {
        $this->validate([
            'form.title' => 'required|string|max:255',
            'form.scheduled_at' => 'nullable|date',
            'form.duration_minutes' => 'nullable|integer|min:1|max:1440',
            'form.location' => 'nullable|string|max:255',
        ]);

        $meeting = $this->newMeeting(trim($this->form['title']), [
            'scheduled_at' => $this->form['scheduled_at'] ?: null,
            'duration_minutes' => $this->form['duration_minutes'] ?: null,
            'location' => $this->form['location'] ?: null,
        ]);

        $this->showCreate = false;

        return $this->redirect(route('gathering.meetings.show', $meeting), navigate: true);
    }

    protected function newMeeting(string $title, array $extra = []): Meeting
    {
        $user = Auth::user();
        abort_unless($user && $user->currentTeam, 403);

        return Meeting::create(array_merge([
            'team_id' => $user->currentTeam->id,
            'host_type' => $this->hostType,
            'host_id' => $this->hostId,
            'title' => $title !== '' ? $title : 'Meeting',
            'status' => MeetingStatus::PLANNED->value,
            'created_by' => $user->id,
        ], $extra));
    }

    public function render()
    {
        $meetings = Meeting::query()
            ->forHost($this->hostType, $this->hostId)
            ->withCount(['agendaItems', 'decisions'])
            ->orderByRaw('COALESCE(scheduled_at, created_at) DESC')
            ->get();

        return view('gathering::livewire.context-meetings', [
            'meetings' => $meetings,
        ]);
    }
}
