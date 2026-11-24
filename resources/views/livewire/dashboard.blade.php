{{--
    Gathering — der Meeting-Arbeitsraum (nx). Weit, zweispaltig: links Einstieg + Hero (nächstes
    Meeting) + Listen, rechts Orientierung („Ein Meeting ist das Ding, alles hängt optional dran").
    Shell = x-ui-*, Inhalt = x-nx-* + var(--nx-*).
--}}
@php
    $next = $upcoming->first();
    $things = [
        ['heroicon-o-list-bullet', 'Agenda', 'Was besprochen wird'],
        ['heroicon-o-clipboard-document-check', 'Beschlüsse', 'Was entschieden wird'],
        ['heroicon-o-document-text', 'Protokoll', 'Was festgehalten wird'],
        ['heroicon-o-users', 'Teilnehmer', 'Wer dabei ist'],
        ['heroicon-o-presentation-chart-bar', 'Präsentationen', 'Folien am Meeting'],
        ['heroicon-o-paper-clip', 'Unterlagen', 'Dateien am Meeting'],
        ['heroicon-o-microphone', 'Aufnahme', 'Transkript → Protokoll'],
    ];
@endphp

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

    <x-ui-page-container width="full" spacing="space-y-8">
      <div class="mx-auto w-full max-w-[1440px]">
        <div class="grid gap-10 xl:grid-cols-[minmax(0,1fr)_300px]">
          {{-- Hauptspalte --}}
          <div class="min-w-0 space-y-10">
            <header class="space-y-3">
                <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">Arbeitsraum</div>
                <h1 class="m-0 text-3xl font-semibold tracking-tight text-[color:var(--nx-text)]">Meetings</h1>
                <p class="m-0 max-w-2xl text-[15px] leading-relaxed text-[color:var(--nx-muted)]">
                    Ein Meeting ist das Ding — dran hängt, was du brauchst: Agenda, Beschlüsse, Protokoll, Folien, Aufnahme. Alles optional.
                </p>
            </header>

            @if($total === 0)
                {{-- Inspirierender Leerzustand: so entsteht ein Meeting --}}
                <section class="rounded-[12px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-8">
                    <div class="max-w-2xl space-y-5">
                        <h2 class="m-0 text-xl font-semibold text-[color:var(--nx-text)]">Dein erstes Meeting</h2>
                        <p class="m-0 text-sm leading-relaxed text-[color:var(--nx-muted)]">
                            Leg es hier an oder direkt im Kontext — an einem Projekt, einer Person. Den Rest füllst du nach Bedarf: eine Agenda,
                            ein paar Folien, am Ende ein Protokoll. Nichts davon ist Pflicht.
                        </p>
                        <ol class="m-0 grid list-none gap-2 p-0 sm:grid-cols-2">
                            @foreach(['Meeting anlegen — Titel genügt', 'Agenda setzen, Teilnehmer einladen', 'Folien & Unterlagen anhängen', 'Protokoll schreiben, Beschlüsse festhalten'] as $n => $step)
                                <li class="flex items-start gap-3 rounded-[8px] border border-[color:var(--nx-line)] px-3 py-2.5 text-sm text-[color:var(--nx-text)]">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-[color:var(--nx-line-strong)] text-[11px] font-semibold tabular-nums text-[color:var(--nx-muted)]">{{ $n + 1 }}</span>
                                    {{ $step }}
                                </li>
                            @endforeach
                        </ol>
                        <div class="pt-1">
                            <x-nx-button variant="primary" wire:click="openCreate">@svg('heroicon-o-plus', 'w-4 h-4') Erstes Meeting anlegen</x-nx-button>
                        </div>
                    </div>
                </section>
            @else
                {{-- Hero: das nächste Meeting --}}
                @if($next)
                    @php
                        $nextWhen = $next->scheduled_at;
                        $nextHost = $next->hostName();
                    @endphp
                    <a href="{{ route('gathering.meetings.show', $next) }}" wire:navigate
                       class="group block rounded-[12px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-6 transition-colors hover:border-[color:var(--nx-line-strong)]">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                            <div class="flex shrink-0 flex-col items-center justify-center rounded-[10px] border border-[color:var(--nx-line)] bg-[color:var(--nx-bg)] px-5 py-3 text-center">
                                <span class="text-[11px] font-semibold uppercase tracking-wide text-[color:var(--nx-faint)]">{{ $nextWhen?->translatedFormat('M') }}</span>
                                <span class="text-2xl font-semibold tabular-nums text-[color:var(--nx-text)]">{{ $nextWhen?->format('d') ?? '—' }}</span>
                                <span class="text-xs text-[color:var(--nx-muted)]">{{ $nextWhen?->translatedFormat('H:i') }}</span>
                            </div>
                            <div class="min-w-0 flex-1 space-y-1.5">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">Als Nächstes</div>
                                <div class="flex items-center gap-2">
                                    <span class="truncate text-lg font-semibold text-[color:var(--nx-text)]">{{ $next->title }}</span>
                                    <x-nx-badge :variant="$next->status?->badge() ?? 'neutral'" dot>{{ $next->status?->label() }}</x-nx-badge>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-sm text-[color:var(--nx-muted)]">
                                    <span>{{ $nextWhen?->translatedFormat('l, d.m.Y') }}</span>
                                    @if($nextHost)<span class="inline-flex items-center gap-1">@svg('heroicon-o-link', 'w-3.5 h-3.5') {{ $nextHost }}</span>@endif
                                    @if(($next->agenda_items_count ?? 0) > 0)<span>{{ $next->agenda_items_count }} Agenda</span>@endif
                                    @if(($next->attendees_count ?? 0) > 0)<span>{{ $next->attendees_count }} Teilnehmer</span>@endif
                                </div>
                            </div>
                            @svg('heroicon-o-arrow-right', 'hidden w-5 h-5 shrink-0 text-[color:var(--nx-faint)] transition-transform group-hover:translate-x-0.5 sm:block')
                        </div>
                    </a>
                @endif

                {{-- Weitere anstehend --}}
                @if($upcoming->count() > ($next ? 1 : 0))
                    <x-nx-section icon="heroicon-o-calendar-days" title="Anstehend" :hint="$upcoming->count()">
                        <x-nx-card flush>
                            @foreach($upcoming->skip($next ? 1 : 0) as $m)
                                @include('gathering::livewire.partials.meeting-row', ['m' => $m])
                            @endforeach
                        </x-nx-card>
                    </x-nx-section>
                @endif

                {{-- Zuletzt / ohne Termin --}}
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
          </div>

          {{-- Rail: Orientierung --}}
          <aside class="space-y-6 self-start xl:sticky xl:top-6">
            <div class="rounded-[12px] border border-[color:var(--nx-line)] p-5">
                <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">Ein Meeting hier</div>
                <p class="mt-2 mb-4 text-sm leading-relaxed text-[color:var(--nx-muted)]">Das Meeting ist das Zentrum. Diese Dinge hängen optional direkt daran:</p>
                <ul class="m-0 list-none space-y-2.5 p-0">
                    @foreach($things as [$icon, $label, $desc])
                        <li class="flex items-start gap-3">
                            @svg($icon, 'mt-0.5 w-4 h-4 shrink-0 text-[color:var(--nx-faint)]')
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-[color:var(--nx-text)]">{{ $label }}</div>
                                <div class="text-xs text-[color:var(--nx-muted)]">{{ $desc }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
          </aside>
        </div>
      </div>
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
