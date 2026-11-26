{{-- Meetings-Reiter im Kontext-Panel: am Host hängende Meetings listen + direkt anlegen.
     Terminal-Idiom (dunkel, --t-*), kein Seiten-Layout. Ein Wurzel-Element. --}}
@php $meetings = $this->meetings; @endphp
<div class="flex flex-col h-full text-[var(--t-text)]">
    <div class="px-3 py-2 border-b border-white/10 text-[11px] text-[var(--t-text-muted)] flex items-center gap-2">
        <span class="flex-1 min-w-0 truncate">
            Meetings an <span class="text-[var(--t-text)] font-medium">{{ $contextSubject ?? ('#'.$contextId) }}</span>
        </span>
        <span class="shrink-0 tabular-nums">{{ $meetings->count() ?: '' }}</span>
    </div>

    <div class="flex-1 min-h-0 overflow-y-auto p-3 space-y-3">
        {{-- Schnell-Anlegen (inline, kein Modal) --}}
        @if($showCreate)
            <div class="rounded border border-white/10 bg-white/5 p-2.5 space-y-2">
                <input type="text" wire:model="form.title" wire:keydown.enter="createMeeting" autofocus
                       placeholder="Titel — z. B. Wochen-Sync"
                       class="w-full rounded bg-black/20 border border-white/10 px-2.5 py-1.5 text-[12px] text-[var(--t-text)] placeholder:text-[var(--t-text-muted)] focus:border-[var(--t-accent)] focus:outline-none" />
                <input type="datetime-local" wire:model="form.scheduled_at"
                       class="w-full rounded bg-black/20 border border-white/10 px-2.5 py-1.5 text-[12px] text-[var(--t-text)] focus:border-[var(--t-accent)] focus:outline-none" />
                @error('form.title') <p class="m-0 text-[11px] text-rose-400">{{ $message }}</p> @enderror
                <div class="flex items-center justify-end gap-2 pt-0.5">
                    <button type="button" wire:click="$set('showCreate', false)"
                            class="px-2 py-1 text-[11px] text-[var(--t-text-muted)] hover:text-[var(--t-text)] transition">Abbrechen</button>
                    <button type="button" wire:click="createMeeting" wire:loading.attr="disabled"
                            class="px-2.5 py-1 rounded text-[11px] font-medium bg-[var(--t-accent)] text-white hover:opacity-90 transition">Anlegen & öffnen</button>
                </div>
            </div>
        @else
            <div class="flex items-center gap-2">
                <button type="button" wire:click="openCreate"
                        class="flex-1 flex items-center justify-center gap-1.5 rounded border border-white/10 bg-white/5 px-2.5 py-1.5 text-[12px] font-medium hover:bg-white/10 transition">
                    @svg('heroicon-o-plus', 'w-3.5 h-3.5') Neues Meeting
                </button>
                @if($meetings->isNotEmpty())
                    <button type="button" wire:click="createFromLast"
                            title="Nächstes aus dem letzten Meeting aufsetzen (Agenda, Teilnehmer, offene Beschlüsse)"
                            class="shrink-0 flex items-center gap-1.5 rounded border border-white/10 px-2.5 py-1.5 text-[12px] text-[var(--t-text-muted)] hover:text-[var(--t-text)] hover:bg-white/5 transition">
                        @svg('heroicon-o-document-duplicate', 'w-3.5 h-3.5') Nächstes
                    </button>
                @endif
            </div>
        @endif

        {{-- Liste --}}
        @forelse($meetings as $m)
            @php $when = $m->scheduled_at?->translatedFormat('d.m.Y · H:i'); @endphp
            <a href="{{ route('gathering.meetings.show', $m) }}" wire:navigate wire:key="cp-meeting-{{ $m->id }}"
               class="group block rounded border border-white/10 bg-white/5 px-2.5 py-2 hover:bg-white/10 transition">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-calendar-days', 'w-3.5 h-3.5 shrink-0 text-[var(--t-text-muted)]')
                    <span class="min-w-0 flex-1 truncate text-[12px] font-medium">{{ $m->title }}</span>
                    @if($m->status?->label())
                        <span class="shrink-0 text-[10px] text-[var(--t-text-muted)]">{{ $m->status->label() }}</span>
                    @endif
                </div>
                <div class="mt-1 flex flex-wrap gap-x-2.5 pl-5 text-[11px] text-[var(--t-text-muted)]">
                    <span>{{ $when ?? 'kein Termin' }}</span>
                    @if(($m->agenda_items_count ?? 0) > 0)<span>{{ $m->agenda_items_count }} Agenda</span>@endif
                    @if(($m->decisions_count ?? 0) > 0)<span>{{ $m->decisions_count }} Beschl.</span>@endif
                </div>
            </a>
        @empty
            @unless($showCreate)
                <p class="m-0 px-1 text-[11px] leading-relaxed text-[var(--t-text-muted)]">
                    Noch kein Meeting an diesem Kontext. Leg das erste an — der Rest (Agenda, Folien, Protokoll) hängt dann im Meeting.
                </p>
            @endunless
        @endforelse
    </div>
</div>
