<?php

namespace Platform\Gathering\Livewire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Platform\Gathering\Models\Meeting;

/**
 * Aufnahmen (Whisper), die DIREKT am Meeting hängen — ein optionales „Ding" des Meetings.
 *
 * Deterministische Anhängung (keine Zeit-Overlap-Korrelation): der Nutzer hängt eine Aufnahme
 * ans Meeting, genau wie ein Slide-Deck. Verknüpft über die whisper-Seam
 * Platform\Whisper\Services\RecordingLinks (Tabelle whisper_recording_links, linkable_type =
 * Morph-Alias 'gathering_meeting'). Nur aktiv, wenn das Whisper-Modul installiert ist.
 *
 * Generisch über die morphMap: funktioniert für jeden Linkable mit view/update-Policy.
 */
class LinkedRecordings extends Component
{
    public string $linkableType;

    public int $linkableId;

    public bool $compact = false;

    public bool $showLink = false;

    public string $linkSearch = '';

    public static function available(): bool
    {
        return class_exists(\Platform\Whisper\Models\WhisperRecording::class)
            && class_exists(\Platform\Whisper\Services\RecordingLinks::class);
    }

    public function mount(string $linkableType, int $linkableId, bool $compact = false): void
    {
        $this->linkableType = $linkableType;
        $this->linkableId = $linkableId;
        $this->compact = $compact;
        Gate::authorize('view', $this->parent());
    }

    protected function parent(): Model
    {
        $class = Relation::getMorphedModel($this->linkableType);
        abort_unless($class && is_subclass_of($class, Model::class), 404);

        return $class::findOrFail($this->linkableId);
    }

    protected function canEdit(): bool
    {
        return Gate::forUser(Auth::user())->allows('update', $this->parent());
    }

    protected function links(): \Platform\Whisper\Services\RecordingLinks
    {
        return app(\Platform\Whisper\Services\RecordingLinks::class);
    }

    public function openLink(): void
    {
        abort_unless(self::available() && $this->canEdit(), 403);
        $this->linkSearch = '';
        $this->showLink = true;
    }

    public function attachRecording(int $recordingId): void
    {
        abort_unless(self::available() && $this->canEdit(), 403);
        $recording = \Platform\Whisper\Models\WhisperRecording::findOrFail($recordingId);
        $this->links()->attach($recording, $this->parent());
        $this->showLink = false;
    }

    public function detachRecording(int $recordingId): void
    {
        abort_unless(self::available() && $this->canEdit(), 403);
        $recording = \Platform\Whisper\Models\WhisperRecording::findOrFail($recordingId);
        $this->links()->detach($recording, $this->parent());
    }

    /** Transkript einer Aufnahme als Protokoll-Entwurf ans Meeting übernehmen (deterministisch, kein Auto). */
    public function toMinutes(int $recordingId): void
    {
        abort_unless(self::available() && $this->canEdit(), 403);
        $parent = $this->parent();
        if (! $parent instanceof Meeting) {
            return;
        }
        $recording = \Platform\Whisper\Models\WhisperRecording::findOrFail($recordingId);
        $content = trim((string) ($recording->transcript ?? ''));
        if ($content === '') {
            return;
        }
        $parent->minutes()->create([
            'content' => "# Protokoll (aus Aufnahme: {$recording->title})\n\n" . $content,
            'created_by' => Auth::id(),
        ]);
        $this->dispatch('context-panel-badges');
    }

    public function render()
    {
        if (! self::available()) {
            return view('gathering::livewire.linked-recordings', ['available' => false, 'recordings' => collect(), 'candidates' => collect(), 'canEdit' => false, 'routes' => [], 'isMeeting' => false]);
        }

        $user = Auth::user();
        $parent = $this->parent();

        $recordings = collect($this->links()->forLinkable($parent))
            ->sortByDesc(fn ($r) => $r->recorded_at?->timestamp ?? $r->created_at?->timestamp ?? 0)
            ->values();

        $candidates = collect();
        if ($this->showLink) {
            $linkedIds = $recordings->pluck('id')->all();
            $candidates = \Platform\Whisper\Models\WhisperRecording::forTeam((int) $parent->team_id)
                ->whereNotIn('id', $linkedIds)
                ->when(trim($this->linkSearch) !== '', fn ($q) => $q->where('title', 'like', '%' . trim($this->linkSearch) . '%'))
                ->orderByDesc('recorded_at')->limit(10)->get();
        }

        return view('gathering::livewire.linked-recordings', [
            'available' => true,
            'recordings' => $recordings,
            'candidates' => $candidates,
            'canEdit' => $this->canEdit(),
            'isMeeting' => $parent instanceof Meeting,
            'routes' => [
                'show' => Route::has('whisper.recordings.show'),
            ],
        ]);
    }
}
