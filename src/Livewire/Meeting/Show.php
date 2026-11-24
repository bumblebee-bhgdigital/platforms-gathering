<?php

namespace Platform\Gathering\Livewire\Meeting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Platform\Core\Livewire\Concerns\HasPageContext;
use Platform\Gathering\Enums\AttendeeRole;
use Platform\Gathering\Enums\DecisionStatus;
use Platform\Gathering\Enums\MeetingStatus;
use Platform\Gathering\Models\AgendaItem;
use Platform\Gathering\Models\Attendee;
use Platform\Gathering\Models\Decision;
use Platform\Gathering\Models\Meeting;
use Platform\Gathering\Models\Minutes;

/**
 * Die Workbench eines Meetings: Agenda, Beschlüsse, Protokoll, Teilnehmer.
 * Host-agnostisch, kein Lenkungsausschuss-Leitfaden. Alles frei schreibbar, keine Automatik.
 */
class Show extends Component
{
    use HasPageContext;

    public Meeting $meeting;

    public bool $showMeetingEdit = false;
    public bool $showItem = false;
    public bool $showDecision = false;
    public bool $showAttendee = false;
    public bool $showMinutes = false;

    public ?int $editingItemId = null;
    public ?int $editingDecisionId = null;
    public ?int $editingMinutesId = null;

    public array $meetingForm = [];
    public array $itemForm = [];
    public array $decisionForm = [];
    public array $attendeeForm = ['user_id' => null, 'role' => 'member'];
    public array $minutesForm = ['content' => ''];

    public function mount(Meeting $meeting): void
    {
        Gate::authorize('view', $meeting);
        $this->meeting = $meeting;
    }

    /** Seitenkontext für das Kontext-Panel: Unterlagen (ContextFiles), Aktivität, Verknüpfungen hängen am Meeting. */
    protected function pageContext(): ?Model
    {
        return $this->meeting;
    }

    protected function pageContextOptions(): array
    {
        return [
            'url' => route('gathering.meetings.show', $this->meeting),
            'source' => 'gathering.meeting.view',
            // Eigene Agenda in der Workbench → die polymorphe Panel-Agenda abschalten (keine Dopplung).
            'apps' => ['agenda' => false],
        ];
    }

    protected function authorizeEdit(): void
    {
        Gate::authorize('update', $this->meeting);
    }

    /** Kind muss zu diesem Meeting gehören; Rechte erbt es über das Meeting. */
    protected function child(string $class, int $id)
    {
        $this->authorizeEdit();

        return $class::where('meeting_id', $this->meeting->id)->findOrFail($id);
    }

    // ── Meeting ──────────────────────────────────────────────

    public function setStatus(string $status): void
    {
        $this->authorizeEdit();
        if (MeetingStatus::tryFrom($status)) {
            $this->meeting->update(['status' => $status]);
        }
    }

    public function openMeetingEdit(): void
    {
        $this->authorizeEdit();
        $m = $this->meeting;
        $this->meetingForm = [
            'title' => $m->title,
            'scheduled_at' => $m->scheduled_at?->format('Y-m-d\TH:i'),
            'duration_minutes' => $m->duration_minutes,
            'location' => $m->location,
        ];
        $this->resetValidation();
        $this->showMeetingEdit = true;
    }

    public function saveMeeting(): void
    {
        $this->authorizeEdit();
        $this->validate([
            'meetingForm.title' => 'required|string|max:255',
            'meetingForm.scheduled_at' => 'nullable|date',
            'meetingForm.duration_minutes' => 'nullable|integer|min:1|max:1440',
            'meetingForm.location' => 'nullable|string|max:255',
        ]);
        $this->meeting->update([
            'title' => trim($this->meetingForm['title']),
            'scheduled_at' => $this->meetingForm['scheduled_at'] ?: null,
            'duration_minutes' => $this->meetingForm['duration_minutes'] ?: null,
            'location' => $this->meetingForm['location'] ?: null,
        ]);
        $this->showMeetingEdit = false;
    }

    public function deleteMeeting()
    {
        Gate::authorize('delete', $this->meeting);
        $this->meeting->delete();

        return $this->redirect(route('gathering.dashboard'), navigate: true);
    }

    // ── Agenda ───────────────────────────────────────────────

    public function openItem(?int $itemId = null): void
    {
        $this->authorizeEdit();
        $this->editingItemId = $itemId;
        $item = $itemId ? $this->child(AgendaItem::class, $itemId) : null;
        $this->itemForm = [
            'title' => $item?->title ?? '',
            'description' => $item?->description ?? '',
            'owner_user_id' => $item?->owner_user_id,
            'duration_minutes' => $item?->duration_minutes,
        ];
        $this->resetValidation();
        $this->showItem = true;
    }

    public function saveItem(): void
    {
        $this->authorizeEdit();
        $this->validate([
            'itemForm.title' => 'required|string|max:255',
            'itemForm.description' => 'nullable|string',
            'itemForm.owner_user_id' => 'nullable|integer|exists:users,id',
            'itemForm.duration_minutes' => 'nullable|integer|min:1|max:1440',
        ]);
        $data = array_map(fn ($v) => $v === '' ? null : $v, $this->itemForm);
        $data['title'] = trim((string) $data['title']);

        if ($this->editingItemId) {
            $this->child(AgendaItem::class, $this->editingItemId)->update($data);
        } else {
            $data['position'] = (int) $this->meeting->agendaItems()->max('position') + 1;
            $data['created_by'] = Auth::id();
            $this->meeting->agendaItems()->create($data);
        }
        $this->showItem = false;
    }

    public function moveItem(int $itemId, int $direction): void
    {
        $this->authorizeEdit();
        $items = $this->meeting->agendaItems()->get()->values();
        $index = $items->search(fn ($i) => $i->id === $itemId);
        $target = $index === false ? false : $index + ($direction < 0 ? -1 : 1);
        if ($index === false || $target < 0 || $target >= $items->count()) {
            return;
        }
        $ordered = $items->all();
        [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];
        DB::transaction(function () use ($ordered) {
            foreach ($ordered as $pos => $item) {
                if ($item->position !== $pos + 1) {
                    $item->update(['position' => $pos + 1]);
                }
            }
        });
    }

    public function deleteItem(int $itemId): void
    {
        $this->child(AgendaItem::class, $itemId)->delete();
    }

    // ── Beschlüsse ───────────────────────────────────────────

    public function openDecision(?int $decisionId = null, ?int $agendaItemId = null): void
    {
        $this->authorizeEdit();
        $this->editingDecisionId = $decisionId;
        $d = $decisionId ? $this->child(Decision::class, $decisionId) : null;
        $this->decisionForm = [
            'text' => $d?->text ?? '',
            'status' => $d?->status?->value ?? DecisionStatus::OPEN->value,
            'owner_user_id' => $d?->owner_user_id,
            'due_date' => $d?->due_date?->toDateString(),
            'agenda_item_id' => $d?->agenda_item_id ?? $agendaItemId,
        ];
        $this->resetValidation();
        $this->showDecision = true;
    }

    public function saveDecision(): void
    {
        $this->authorizeEdit();
        $this->validate([
            'decisionForm.text' => 'required|string',
            'decisionForm.status' => 'required|in:' . implode(',', DecisionStatus::values()),
            'decisionForm.owner_user_id' => 'nullable|integer|exists:users,id',
            'decisionForm.due_date' => 'nullable|date',
            'decisionForm.agenda_item_id' => 'nullable|integer',
        ]);
        $data = array_map(fn ($v) => $v === '' ? null : $v, $this->decisionForm);
        $data['text'] = trim((string) $data['text']);
        if ($data['agenda_item_id'] && ! $this->meeting->agendaItems()->whereKey($data['agenda_item_id'])->exists()) {
            $data['agenda_item_id'] = null;
        }
        if ($data['owner_user_id']) {
            $data['owner_agent_id'] = null;
        }

        if ($this->editingDecisionId) {
            $this->child(Decision::class, $this->editingDecisionId)->update($data);
        } else {
            $data['created_by'] = Auth::id();
            $this->meeting->decisions()->create($data);
        }
        $this->showDecision = false;
    }

    public function setDecisionStatus(int $decisionId, string $status): void
    {
        if (DecisionStatus::tryFrom($status)) {
            $this->child(Decision::class, $decisionId)->update(['status' => $status]);
        }
    }

    public function deleteDecision(int $decisionId): void
    {
        $this->child(Decision::class, $decisionId)->delete();
    }

    // ── Teilnehmer ───────────────────────────────────────────

    public function openAttendee(): void
    {
        $this->authorizeEdit();
        $this->attendeeForm = ['user_id' => null, 'role' => 'member'];
        $this->resetValidation();
        $this->showAttendee = true;
    }

    public function addAttendee(): void
    {
        $this->authorizeEdit();
        $this->validate([
            'attendeeForm.user_id' => 'required|integer|exists:users,id',
            'attendeeForm.role' => 'nullable|string|max:50',
        ]);
        $user = \Platform\Core\Models\User::find((int) $this->attendeeForm['user_id']);
        $col = ($user->type ?? null) === 'ai_user' ? 'agent_id' : 'user_id';
        $exists = $this->meeting->attendees()
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('agent_id', $user->id))->exists();
        if (! $exists) {
            $this->meeting->attendees()->create([
                $col => $user->id,
                'role' => $this->attendeeForm['role'] ?: null,
                'attended' => false,
            ]);
        }
        $this->showAttendee = false;
    }

    public function toggleAttended(int $attendeeId): void
    {
        $a = $this->child(Attendee::class, $attendeeId);
        $a->update(['attended' => ! $a->attended]);
    }

    public function removeAttendee(int $attendeeId): void
    {
        $this->child(Attendee::class, $attendeeId)->delete();
    }

    // ── Protokoll ────────────────────────────────────────────

    public function openMinutes(?int $minutesId = null): void
    {
        $this->authorizeEdit();
        $this->editingMinutesId = $minutesId;
        $min = $minutesId ? $this->child(Minutes::class, $minutesId) : null;
        $this->minutesForm = ['content' => $min?->content ?? $this->minutesTemplate()];
        $this->showMinutes = true;
    }

    /** Vorschlag aus der Agenda — nur Vorbelegung im Editor, nichts wird automatisch gespeichert. */
    protected function minutesTemplate(): string
    {
        $lines = ['# Protokoll ' . $this->meeting->title, ''];
        foreach ($this->meeting->agendaItems as $i => $item) {
            $lines[] = '## ' . ($i + 1) . '. ' . $item->title;
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    public function saveMinutes(): void
    {
        $this->authorizeEdit();
        $this->validate(['minutesForm.content' => 'nullable|string']);
        if ($this->editingMinutesId) {
            $this->child(Minutes::class, $this->editingMinutesId)->update(['content' => $this->minutesForm['content']]);
        } else {
            $this->meeting->minutes()->create(['content' => $this->minutesForm['content'], 'created_by' => Auth::id()]);
        }
        $this->showMinutes = false;
    }

    public function toggleMinutesApproval(int $minutesId): void
    {
        $min = $this->child(Minutes::class, $minutesId);
        $min->update($min->approved_at
            ? ['approved_at' => null, 'approved_by' => null]
            : ['approved_at' => now(), 'approved_by' => Auth::id()]);
    }

    public function deleteMinutes(int $minutesId): void
    {
        $this->child(Minutes::class, $minutesId)->delete();
    }

    // ── Render ───────────────────────────────────────────────

    public function render()
    {
        $meeting = $this->meeting->fresh([
            'agendaItems.ownerUser:id,name',
            'agendaItems.ownerAgent:id,name',
            'decisions.ownerUser:id,name',
            'decisions.ownerAgent:id,name',
            'attendees.user:id,name',
            'attendees.agent:id,name',
            'minutes.approver:id,name',
        ]);
        $this->meeting = $meeting;

        $items = $meeting->agendaItems;

        return view('gathering::livewire.meeting.show', [
            'meeting' => $meeting,
            'hostName' => $meeting->hostName(),
            'items' => $items,
            'decisions' => $meeting->decisions
                ->sortBy(fn ($d) => [$d->status?->value === 'open' ? 0 : 1, $d->due_date?->timestamp ?? PHP_INT_MAX])
                ->values(),
            'canEdit' => Gate::allows('update', $meeting),
            'statusOptions' => MeetingStatus::cases(),
            'decisionStatusOptions' => array_map(fn ($s) => ['value' => $s->value, 'label' => $s->label()], DecisionStatus::cases()),
            'roleOptions' => array_map(fn ($r) => ['value' => $r->value, 'label' => $r->label()], AttendeeRole::cases()),
            'userOptions' => $this->teamUsers((int) $meeting->team_id),
            'itemOptions' => $items->values()->map(fn ($i, $k) => ['value' => $i->id, 'label' => ($k + 1) . '. ' . $i->title])->all(),
        ])->layout('platform::layouts.app');
    }

    protected function teamUsers(int $teamId): array
    {
        $ids = DB::table('team_user')->where('team_id', $teamId)->pluck('user_id');

        return \Platform\Core\Models\User::whereIn('id', $ids)->orderBy('name')->get(['id', 'name'])
            ->map(fn ($u) => ['value' => $u->id, 'label' => $u->name])->all();
    }

    public static function decisionBadge(?DecisionStatus $s): string
    {
        return $s?->badge() ?? 'warning';
    }

    public static function meetingBadge(?MeetingStatus $s): string
    {
        return $s?->badge() ?? 'neutral';
    }
}
