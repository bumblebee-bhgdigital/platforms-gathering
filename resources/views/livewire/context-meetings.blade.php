{{--
    Einbettbarer Meetings-Block für einen Host-Kontext. Rendert inline (ein Wurzel-Element).
    Zeigt die Meeting-Liste dieses Hosts (= die „Serie" am Kontext) und legt im Kontext neue an.
--}}
<div class="space-y-3">
    <div class="flex items-center justify-between gap-2">
        <h2 class="m-0 text-sm font-semibold text-[color:var(--nx-text)]">
            Meetings <span class="font-normal text-[color:var(--nx-faint)]">{{ $meetings->count() }}</span>
        </h2>
        <div class="flex items-center gap-1.5">
            @if($meetings->isNotEmpty())
                <x-nx-button variant="ghost" size="sm" wire:click="createFromLast" title="Neues Meeting aus dem letzten aufsetzen">
                    @svg('heroicon-o-document-duplicate', 'w-4 h-4') Nächstes
                </x-nx-button>
            @endif
            <x-nx-button variant="secondary" size="sm" wire:click="openCreate">
                @svg('heroicon-o-plus', 'w-4 h-4') Meeting
            </x-nx-button>
        </div>
    </div>

    <x-nx-card flush>
        @forelse($meetings as $m)
            @php $when = $m->scheduled_at?->translatedFormat('d.m.Y · H:i'); @endphp
            <a href="{{ route('gathering.meetings.show', $m) }}" wire:navigate
               class="group flex items-center gap-3 border-b border-[color:var(--nx-line)] px-4 py-3 last:border-b-0 transition-colors hover:bg-[color:var(--nx-hover)]"
               wire:key="ctx-meeting-{{ $m->id }}">
                @svg('heroicon-o-calendar-days', 'w-4 h-4 shrink-0 text-[color:var(--nx-faint)]')
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="truncate text-sm font-medium text-[color:var(--nx-text)]">{{ $m->title }}</span>
                        <x-nx-badge :variant="$m->status?->badge() ?? 'neutral'" dot>{{ $m->status?->label() }}</x-nx-badge>
                    </div>
                    <div class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-[color:var(--nx-muted)]">
                        <span>{{ $when ?? 'kein Termin' }}</span>
                        @if(($m->agenda_items_count ?? 0) > 0)<span>{{ $m->agenda_items_count }} Agenda</span>@endif
                        @if(($m->decisions_count ?? 0) > 0)<span>{{ $m->decisions_count }} Beschlüsse</span>@endif
                    </div>
                </div>
                @svg('heroicon-o-chevron-right', 'w-4 h-4 shrink-0 text-[color:var(--nx-faint)] opacity-0 transition-opacity group-hover:opacity-100')
            </a>
        @empty
            <div class="space-y-3 p-4">
                <p class="m-0 text-sm leading-relaxed text-[color:var(--nx-muted)]">Noch kein Meeting zu {{ $hostLabel ?? 'diesem Kontext' }}.</p>
                <x-nx-button size="sm" wire:click="openCreate">Erstes Meeting anlegen</x-nx-button>
            </div>
        @endforelse
    </x-nx-card>

    {{-- Neues Meeting (im Kontext) --}}
    <x-nx-modal wire:model="showCreate" size="md">
        <x-slot name="header">Neues Meeting{{ $hostLabel ? ' · ' . $hostLabel : '' }}</x-slot>
        <div class="space-y-4">
            <x-nx-input-text name="form.title" label="Titel" wire:model="form.title" required />
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-[color:var(--nx-text)]">Termin</label>
                    <input type="datetime-local" wire:model="form.scheduled_at"
                           class="w-full rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm text-[color:var(--nx-text)]" />
                </div>
                <x-nx-input-number name="form.duration_minutes" label="Dauer (Min.)" wire:model="form.duration_minutes" />
            </div>
            <x-nx-input-text name="form.location" label="Ort / Link" wire:model="form.location" />
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <x-nx-button variant="ghost" wire:click="$set('showCreate', false)">Abbrechen</x-nx-button>
                <x-nx-button variant="primary" wire:click="createMeeting">Anlegen & öffnen</x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>
</div>
