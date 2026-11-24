{{--
    Gathering — Übersicht aller Meetings des Teams.
    Ein Meeting ist das Ding; hier host-los angelegt, der Host (worum's geht) folgt optional.
    nx-Design: Shell = x-ui-*, Inhalt = x-nx-* + var(--nx-*).
--}}
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Meetings" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Meetings', 'icon' => 'calendar-days'],
        ]">
            <x-nx-button variant="primary" size="sm" wire:click="openCreate">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Neues Meeting</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container width="contained" spacing="space-y-8">
        @if($total === 0)
            <x-nx-card>
                <x-nx-empty icon="heroicon-o-calendar-days">
                    Noch keine Meetings. Ein Meeting trägt, was du brauchst — Agenda, Beschlüsse, Protokoll, Teilnehmer. Alles optional.
                    <x-slot name="action">
                        <x-nx-button variant="primary" size="sm" wire:click="openCreate">Erstes Meeting anlegen</x-nx-button>
                    </x-slot>
                </x-nx-empty>
            </x-nx-card>
        @else
            {{-- Anstehend --}}
            <x-nx-section icon="heroicon-o-calendar-days" title="Anstehend" :hint="$upcoming->count()">
                @if($upcoming->isEmpty())
                    <x-nx-card>
                        <p class="m-0 p-2 text-sm leading-relaxed text-[color:var(--nx-muted)]">Nichts terminiert. Neues Meeting anlegen oder einem Kontext (Projekt …) hinzufügen.</p>
                    </x-nx-card>
                @else
                    <x-nx-card flush>
                        @foreach($upcoming as $m)
                            @include('gathering::livewire.partials.meeting-row', ['m' => $m])
                        @endforeach
                    </x-nx-card>
                @endif
            </x-nx-section>

            {{-- Vergangen / ohne Termin --}}
            @if($recent->isNotEmpty())
                <x-nx-section icon="heroicon-o-clock" title="Zuletzt" :hint="$recent->count()">
                    <x-nx-card flush>
                        @foreach($recent as $m)
                            @include('gathering::livewire.partials.meeting-row', ['m' => $m])
                        @endforeach
                    </x-nx-card>
                </x-nx-section>
            @endif
        @endif
    </x-ui-page-container>

    {{-- Neues Meeting --}}
    <x-nx-modal wire:model="showCreate" size="md">
        <x-slot name="header">Neues Meeting</x-slot>
        <div class="space-y-4">
            <x-nx-input-text name="form.title" label="Titel" wire:model="form.title" placeholder="z. B. Wochen-Sync" required />
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-[color:var(--nx-text)]">Termin</label>
                    <input type="datetime-local" wire:model="form.scheduled_at"
                           class="w-full rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm text-[color:var(--nx-text)]" />
                    <p class="mt-1 text-xs text-[color:var(--nx-faint)]">Optional — mit Termin erscheint es im Eingang.</p>
                </div>
                <x-nx-input-number name="form.duration_minutes" label="Dauer (Min.)" wire:model="form.duration_minutes" placeholder="60" />
            </div>
            <x-nx-input-text name="form.location" label="Ort / Link" wire:model="form.location" placeholder="Raum oder Videolink" />
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <x-nx-button variant="ghost" wire:click="$set('showCreate', false)">Abbrechen</x-nx-button>
                <x-nx-button variant="primary" wire:click="createMeeting">Anlegen & öffnen</x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>
</x-ui-page>
