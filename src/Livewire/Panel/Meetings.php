<?php

namespace Platform\Gathering\Livewire\Panel;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Platform\Core\Livewire\Terminal\Concerns\WithTerminalContext;
use Platform\Gathering\Enums\MeetingStatus;
use Platform\Gathering\Models\Meeting;
use Platform\Gathering\Terminal\MeetingsPanelApp;

/**
 * Meetings-Reiter im Kontext-Panel: die am aktuellen Datensatz hängenden Meetings
 * (die „Serie" am Kontext) listen und direkt in der Sidebar ein neues anlegen.
 * Das Meeting selbst wird im Workbench (gathering.meetings.show) bearbeitet.
 *
 * Kontext kommt über WithTerminalContext: contextType (FQCN des Host-Modells) + contextId.
 * Gespeichert wird host_type als Morph-Alias (z. B. 'project') — darum die Umrechnung.
 */
class Meetings extends Component
{
    use WithTerminalContext;

    public bool $showCreate = false;

    public array $form = ['title' => '', 'scheduled_at' => null, 'duration_minutes' => null, 'location' => null];

    /** Morph-Alias des Hosts (z. B. 'project'), unter dem Meeting.host_type abgelegt wird. */
    protected function hostType(): ?string
    {
        return MeetingsPanelApp::hostAliasFor($this->contextType);
    }

    #[Computed]
    public function meetings()
    {
        if (! $this->hasContext()) {
            return collect();
        }

        return Meeting::query()
            ->forHost($this->hostType(), (int) $this->contextId)
            ->withCount(['agendaItems', 'decisions'])
            ->orderByRaw('COALESCE(scheduled_at, created_at) DESC')
            ->get();
    }

    public function openCreate(): void
    {
        $this->form = ['title' => '', 'scheduled_at' => null, 'duration_minutes' => null, 'location' => null];
        $this->resetValidation();
        $this->showCreate = true;
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

    /** „Nächstes aus dem letzten": Titel/Agenda/Teilnehmer/offene Beschlüsse aus dem jüngsten Meeting übernehmen. */
    public function createFromLast()
    {
        if (! $this->hasContext()) {
            return null;
        }

        $last = Meeting::query()->forHost($this->hostType(), (int) $this->contextId)
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

    protected function newMeeting(string $title, array $extra = []): Meeting
    {
        $user = Auth::user();
        abort_unless($user && $user->currentTeam, 403);
        abort_unless($this->hasContext(), 404);

        return Meeting::create(array_merge([
            'team_id' => $user->currentTeam->id,
            'host_type' => $this->hostType(),
            'host_id' => (int) $this->contextId,
            'title' => $title !== '' ? $title : 'Meeting',
            'status' => MeetingStatus::PLANNED->value,
            'created_by' => $user->id,
        ], $extra));
    }

    public function render()
    {
        return view('gathering::livewire.panel.meetings');
    }
}
