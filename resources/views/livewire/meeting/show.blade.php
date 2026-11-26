{{--
    Meeting-Workbench (nx): Kopf, Agenda, Beschlüsse, Protokoll + Seitenspalte Teilnehmer.
    Host-agnostisch, kein LA-Leitfaden. Alles frei schreibbar, keine Automatik.
--}}
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$meeting->title" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="array_values(array_filter([
            ['label' => 'Meetings', 'href' => route('gathering.dashboard'), 'icon' => 'calendar-days'],
            $hostName ? ['label' => $hostName] : null,
            ['label' => $meeting->title],
        ]))">
            @if($canEdit)
                <x-nx-dropdown label="Aktionen">
                    <x-nx-dropdown-item wire:click="openMeetingEdit">@svg('heroicon-o-pencil-square', 'w-4 h-4') Bearbeiten</x-nx-dropdown-item>
                    <x-nx-dropdown-item wire:click="openItem">@svg('heroicon-o-plus', 'w-4 h-4') Agenda-Punkt</x-nx-dropdown-item>
                    <x-nx-dropdown-item wire:click="openDecision">@svg('heroicon-o-clipboard-document-check', 'w-4 h-4') Beschluss</x-nx-dropdown-item>
                    <x-nx-dropdown-divider />
                    <x-nx-dropdown-item variant="danger" wire:click="deleteMeeting" wire:confirm="Meeting löschen? (Soft-Delete)">@svg('heroicon-o-trash', 'w-4 h-4') Löschen</x-nx-dropdown-item>
                </x-nx-dropdown>
            @endif
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container width="full" spacing="space-y-8">
        @php
            $openDec = $decisions->where('status.value', 'open');
            $overdueDec = $openDec->filter(fn ($d) => $d->due_date && $d->due_date->isPast())->count();
            $present = $meeting->attendees->where('attended', true)->count();
            $planned = $items->sum('duration_minutes');
        @endphp
        <div class="mx-auto w-full max-w-[1280px] space-y-8">
            {{-- Kopf --}}
            @php $origin = \Platform\Gathering\Support\HostChip::for($meeting); @endphp
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-semibold uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">Arbeitsraum</span>
                    {{-- Herkunft: woran hängt dieses Meeting? Klickbar zur Quelle (jump-to-source). --}}
                    @if($origin && $origin['url'])
                        <a href="{{ $origin['url'] }}" wire:navigate
                           class="group inline-flex items-center gap-1.5 rounded-full border border-[color:var(--nx-line)] px-2.5 py-1 text-xs text-[color:var(--nx-muted)] transition-colors hover:border-[color:var(--nx-line-strong)] hover:text-[color:var(--nx-text)]">
                            @svg($origin['icon'], 'w-3.5 h-3.5 text-[color:var(--nx-faint)]')
                            <span class="text-[color:var(--nx-faint)]">gehört zu</span>
                            <span class="font-medium text-[color:var(--nx-text)]">{{ $origin['type'] }}</span>
                            @if($origin['name'])<span>· {{ $origin['name'] }}</span>@endif
                            @svg('heroicon-o-arrow-up-right', 'w-3 h-3 text-[color:var(--nx-faint)] transition-transform group-hover:-translate-y-px group-hover:translate-x-px')
                        </a>
                    @elseif($origin)
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-[color:var(--nx-line)] px-2.5 py-1 text-xs text-[color:var(--nx-muted)]">
                            @svg($origin['icon'], 'w-3.5 h-3.5 text-[color:var(--nx-faint)]')
                            <span class="text-[color:var(--nx-faint)]">gehört zu</span>
                            <span class="font-medium text-[color:var(--nx-text)]">{{ $origin['type'] }}</span>
                            @if($origin['name'])<span>· {{ $origin['name'] }}</span>@endif
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-dashed border-[color:var(--nx-line-strong)] px-2.5 py-1 text-xs text-[color:var(--nx-faint)]"
                              title="Dieses Meeting ist noch keinem Kontext zugeordnet.">
                            @svg('heroicon-o-link-slash', 'w-3.5 h-3.5')
                            keinem Kontext zugeordnet
                        </span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="m-0 text-3xl font-semibold tracking-tight text-[color:var(--nx-text)]">{{ $meeting->title }}</h1>
                    <x-nx-badge :variant="\Platform\Gathering\Livewire\Meeting\Show::meetingBadge($meeting->status)" dot>{{ $meeting->status?->label() }}</x-nx-badge>
                </div>
                <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm text-[color:var(--nx-muted)]">
                    <span class="inline-flex items-center gap-1.5">@svg('heroicon-o-calendar-days', 'w-4 h-4 text-[color:var(--nx-faint)]') {{ $meeting->scheduled_at?->translatedFormat('l, d.m.Y · H:i') ?? 'kein Termin' }}</span>
                    @if($meeting->duration_minutes)<span class="inline-flex items-center gap-1.5">@svg('heroicon-o-clock', 'w-4 h-4 text-[color:var(--nx-faint)]') {{ $meeting->duration_minutes }} Min.</span>@endif
                    @if($meeting->location)<span class="inline-flex items-center gap-1.5">@svg('heroicon-o-map-pin', 'w-4 h-4 text-[color:var(--nx-faint)]') {{ $meeting->location }}</span>@endif
                </div>
                @if($canEdit)
                    <div class="flex flex-wrap items-center gap-1 pt-1">
                        <span class="mr-1 text-xs text-[color:var(--nx-faint)]">Status:</span>
                        @foreach($statusOptions as $s)
                            <button type="button" wire:click="setStatus('{{ $s->value }}')"
                                    class="rounded-full px-3 py-1 text-xs transition-colors {{ $meeting->status === $s ? 'bg-[color:var(--nx-accent)] text-[color:var(--nx-on-accent)]' : 'text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)]' }}">
                                {{ $s->label() }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            @php
                $flow = match ($meeting->status?->value) {
                    'planned' => ['heroicon-o-pencil-square', 'Vorbereiten', 'Agenda füllen, Teilnehmer einladen, Unterlagen und Folien anhängen.'],
                    'prepared' => ['heroicon-o-chat-bubble-left-right', 'Durchführen', 'Punkte durchgehen, Beschlüsse festhalten, Protokoll mitschreiben.'],
                    'held' => ['heroicon-o-clipboard-document-check', 'Nachbereiten', 'Protokoll freigeben, Beschlüsse verfolgen, Aufnahme anhängen.'],
                    'cancelled' => ['heroicon-o-x-circle', 'Abgesagt', 'Offene Punkte gehen nicht verloren — nimm sie ins nächste Meeting mit.'],
                    default => null,
                };
            @endphp
            @if($flow)
                <div class="flex items-start gap-3 rounded-[8px] border border-[color:var(--nx-line)] px-5 py-4">
                    @svg($flow[0], 'mt-0.5 w-5 h-5 shrink-0 text-[color:var(--nx-muted)]')
                    <div class="space-y-0.5">
                        <div class="text-sm font-semibold text-[color:var(--nx-text)]">Jetzt: {{ $flow[1] }}</div>
                        <p class="m-0 text-sm leading-relaxed text-[color:var(--nx-muted)]">{{ $flow[2] }}</p>
                    </div>
                </div>
            @endif

            {{-- Anforderung des Hosts (z. B. LA-Leitfaden eines Gremiums). Abrufbar, nicht kopiert:
                 lebt beim Host, hier nur gelesen — Mensch wie Agent sehen, was gefordert ist. --}}
            @php $briefing = \Platform\Gathering\Support\HostChip::briefingFor($meeting); @endphp
            @if($briefing)
                <section class="rounded-[12px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-6">
                    <div class="flex items-start gap-3">
                        @svg('heroicon-o-' . ($briefing->icon ?: 'clipboard-document-list'), 'mt-0.5 w-5 h-5 shrink-0 text-[color:var(--nx-faint)]')
                        <div class="min-w-0 flex-1 space-y-3">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span class="text-[11px] font-semibold uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">Anforderung</span>
                                <h2 class="m-0 text-base font-semibold text-[color:var(--nx-text)]">{{ $briefing->title }}</h2>
                                @if($briefing->url)
                                    <a href="{{ $briefing->url }}" wire:navigate class="inline-flex items-center gap-1 text-xs text-[color:var(--nx-muted)] transition-colors hover:text-[color:var(--nx-text)]">
                                        @svg('heroicon-o-arrow-up-right', 'w-3 h-3') zur Quelle
                                    </a>
                                @endif
                            </div>
                            @if($briefing->summary)
                                <p class="m-0 max-w-[70ch] text-sm leading-relaxed text-[color:var(--nx-muted)]">{{ $briefing->summary }}</p>
                            @endif
                            @php $briefingPoints = $briefing->normalizedPoints(); @endphp
                            @if(count($briefingPoints))
                                <ul class="m-0 grid list-none gap-1.5 p-0 sm:grid-cols-2">
                                    @foreach($briefingPoints as $bp)
                                        <li class="flex items-start gap-2 text-sm text-[color:var(--nx-text)]">
                                            @svg('heroicon-o-check-circle', 'mt-0.5 w-4 h-4 shrink-0 text-[color:var(--nx-faint)]')
                                            <span><span class="font-medium">{{ $bp['label'] }}</span>@if($bp['hint']) <span class="text-[color:var(--nx-muted)]">— {{ $bp['hint'] }}</span>@endif</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            <p class="m-0 text-[11px] text-[color:var(--nx-faint)]">Kommt vom Host — hier nur gezeigt, gepflegt wird sie an der Quelle.</p>
                        </div>
                    </div>
                </section>
            @endif

            <div class="grid gap-10 xl:grid-cols-[minmax(0,1fr)_300px]">
                {{-- Hauptspalte --}}
                <div class="min-w-0 space-y-10">
                    <x-nx-stat-grid :cols="3">
                        <x-nx-stat label="Agenda" :value="$items->count() . ' Punkte'" icon="heroicon-o-list-bullet"
                                   :hint="$planned ? $planned . ' Min. geplant' : 'frei gestaltbar'" />
                        <x-nx-stat label="Offene Beschlüsse" :value="$openDec->count()" icon="heroicon-o-clipboard-document-check"
                                   :hint="$overdueDec ? $overdueDec . ' überfällig' : $decisions->count() . ' insgesamt'" :accent="$overdueDec ? 'var(--nx-danger)' : null" />
                        <x-nx-stat label="Teilnehmer" :value="$present . ' / ' . $meeting->attendees->count()" icon="heroicon-o-users" hint="anwesend / eingeladen" />
                    </x-nx-stat-grid>

                    {{-- Agenda --}}
                    <section class="space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="max-w-2xl space-y-1">
                                <h2 class="m-0 text-base font-semibold text-[color:var(--nx-text)]">Agenda <span class="font-normal text-[color:var(--nx-faint)]">{{ $items->count() }}</span></h2>
                                <p class="m-0 text-sm leading-relaxed text-[color:var(--nx-muted)]">Was besprochen werden soll — in der Reihenfolge, in der ihr es angeht.</p>
                            </div>
                            @if($canEdit)
                                <x-nx-button size="sm" wire:click="openItem">@svg('heroicon-o-plus', 'w-4 h-4') Punkt</x-nx-button>
                            @endif
                        </div>
                        <x-nx-card flush>
                            @forelse($items as $idx => $item)
                                <div class="group flex items-start gap-3 border-b border-[color:var(--nx-line)] px-5 py-3.5 last:border-b-0" wire:key="item-{{ $item->id }}">
                                    <span class="mt-0.5 w-5 shrink-0 text-right text-sm tabular-nums text-[color:var(--nx-faint)]">{{ $idx + 1 }}</span>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-medium text-[color:var(--nx-text)]">{{ $item->title }}</div>
                                        @if($item->description)<p class="m-0 mt-0.5 whitespace-pre-line text-sm leading-relaxed text-[color:var(--nx-muted)]">{{ $item->description }}</p>@endif
                                        <div class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-[color:var(--nx-muted)]">
                                            @if($item->ownerUser)<span>{{ $item->ownerUser->name }}</span>@endif
                                            @if($item->duration_minutes)<span>{{ $item->duration_minutes }} Min.</span>@endif
                                        </div>
                                    </div>
                                    @if($canEdit)
                                        <div class="flex shrink-0 items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                            <button type="button" wire:click="moveItem({{ $item->id }}, -1)" class="text-[color:var(--nx-muted)] hover:text-[color:var(--nx-text)]" title="Hoch">@svg('heroicon-o-chevron-up', 'w-4 h-4')</button>
                                            <button type="button" wire:click="moveItem({{ $item->id }}, 1)" class="text-[color:var(--nx-muted)] hover:text-[color:var(--nx-text)]" title="Runter">@svg('heroicon-o-chevron-down', 'w-4 h-4')</button>
                                            <button type="button" wire:click="openDecision(null, {{ $item->id }})" class="text-[color:var(--nx-muted)] hover:text-[color:var(--nx-text)]" title="Beschluss zu diesem Punkt">@svg('heroicon-o-clipboard-document-check', 'w-4 h-4')</button>
                                            <button type="button" wire:click="openItem({{ $item->id }})" class="text-[color:var(--nx-muted)] hover:text-[color:var(--nx-text)]" title="Bearbeiten">@svg('heroicon-o-pencil-square', 'w-4 h-4')</button>
                                            <button type="button" wire:click="deleteItem({{ $item->id }})" wire:confirm="Agenda-Punkt löschen?" class="text-[color:var(--nx-muted)] hover:text-[color:var(--nx-danger)]" title="Löschen">@svg('heroicon-o-trash', 'w-4 h-4')</button>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="space-y-3 p-5">
                                    <p class="m-0 text-sm leading-relaxed text-[color:var(--nx-muted)]">Noch keine Agenda.</p>
                                    @if($canEdit)
                                        <x-nx-button size="sm" wire:click="openItem">Ersten Punkt anlegen</x-nx-button>
                                    @endif
                                </div>
                            @endforelse
                        </x-nx-card>
                    </section>

                    {{-- Präsentationen (Slides, lose gekoppelt — nur wenn Slides-Modul installiert) --}}
                    @if(\Platform\Gathering\Livewire\LinkedDecks::available())
                        <livewire:gathering.linked-decks linkable-type="gathering_meeting" :linkable-id="$meeting->id" :key="'decks-'.$meeting->id" />
                    @endif

                    {{-- Aufnahmen (Whisper, direkt am Meeting — nur wenn Whisper-Modul installiert) --}}
                    @if(\Platform\Gathering\Livewire\LinkedRecordings::available())
                        <livewire:gathering.linked-recordings linkable-type="gathering_meeting" :linkable-id="$meeting->id" :key="'recs-'.$meeting->id" />
                    @endif

                    {{-- Beschlüsse --}}
                    <section class="space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="max-w-2xl space-y-1">
                                <h2 class="m-0 text-base font-semibold text-[color:var(--nx-text)]">Beschlüsse <span class="font-normal text-[color:var(--nx-faint)]">{{ $decisions->count() }}</span></h2>
                                <p class="m-0 text-sm leading-relaxed text-[color:var(--nx-muted)]">Was entschieden wurde — mit Verantwortlichem und Frist.</p>
                            </div>
                            @if($canEdit)
                                <x-nx-button variant="secondary" size="sm" wire:click="openDecision">@svg('heroicon-o-plus', 'w-4 h-4') Beschluss</x-nx-button>
                            @endif
                        </div>
                        <x-nx-card flush>
                            @forelse($decisions as $d)
                                @php $od = $d->status?->value === 'open' && $d->due_date && $d->due_date->isPast(); @endphp
                                <div class="group flex items-start gap-3 border-b border-[color:var(--nx-line)] px-5 py-4 last:border-b-0" wire:key="decision-{{ $d->id }}">
                                    @if($canEdit)
                                        <button type="button" title="{{ $d->status?->value === 'implemented' ? 'Wieder öffnen' : 'Als umgesetzt markieren' }}"
                                                wire:click="setDecisionStatus({{ $d->id }}, '{{ $d->status?->value === 'implemented' ? 'open' : 'implemented' }}')"
                                                class="mt-0.5 shrink-0 {{ $d->status?->value === 'implemented' ? 'text-[color:var(--nx-success)]' : 'text-[color:var(--nx-faint)] hover:text-[color:var(--nx-text)]' }}">
                                            @svg($d->status?->value === 'implemented' ? 'heroicon-s-check-circle' : 'heroicon-o-check-circle', 'w-5 h-5')
                                        </button>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm leading-relaxed {{ $d->status?->value === 'discarded' ? 'text-[color:var(--nx-faint)] line-through' : 'text-[color:var(--nx-text)]' }}">{{ $d->text }}</div>
                                        <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-[color:var(--nx-muted)]">
                                            @if($d->agenda_item_id && ($ai = $items->firstWhere('id', $d->agenda_item_id)))<span>zu {{ $ai->title }}</span>@endif
                                            @if($d->ownerUser || $d->ownerAgent)<span>{{ $d->ownerUser?->name ?? $d->ownerAgent?->name }}</span>@endif
                                            @if($d->due_date)<span class="{{ $od ? 'font-medium text-[color:var(--nx-danger)]' : '' }}">fällig {{ $d->due_date->format('d.m.Y') }}</span>@endif
                                        </div>
                                    </div>
                                    <x-nx-badge :variant="$od ? 'danger' : \Platform\Gathering\Livewire\Meeting\Show::decisionBadge($d->status)">{{ $od ? 'Überfällig' : $d->status?->label() }}</x-nx-badge>
                                    @if($canEdit)
                                        <div class="flex shrink-0 gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                            <button type="button" wire:click="openDecision({{ $d->id }})" class="text-[color:var(--nx-muted)] hover:text-[color:var(--nx-text)]" title="Bearbeiten">@svg('heroicon-o-pencil-square', 'w-4 h-4')</button>
                                            <button type="button" wire:click="deleteDecision({{ $d->id }})" wire:confirm="Beschluss löschen?" class="text-[color:var(--nx-muted)] hover:text-[color:var(--nx-danger)]" title="Löschen">@svg('heroicon-o-trash', 'w-4 h-4')</button>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="m-0 px-5 py-6 text-sm leading-relaxed text-[color:var(--nx-muted)]">Noch keine Beschlüsse. Am schnellsten über „Beschluss zu diesem Punkt" an der Agenda.</p>
                            @endforelse
                        </x-nx-card>
                    </section>

                    {{-- Protokoll --}}
                    <section class="space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="max-w-2xl space-y-1">
                                <h2 class="m-0 text-base font-semibold text-[color:var(--nx-text)]">Protokoll</h2>
                                <p class="m-0 text-sm leading-relaxed text-[color:var(--nx-muted)]">Was verstanden und entschieden wurde. Erst mit Freigabe gilt es als gemeinsamer Stand.</p>
                            </div>
                            @if($canEdit)
                                <x-nx-button variant="secondary" size="sm" wire:click="openMinutes">@svg('heroicon-o-plus', 'w-4 h-4') Protokoll</x-nx-button>
                            @endif
                        </div>
                        @forelse($meeting->minutes as $min)
                            <x-nx-card wire:key="minutes-{{ $min->id }}">
                                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                                    @if($min->approved_at)
                                        <x-nx-badge variant="success" dot>Freigegeben {{ $min->approved_at->format('d.m.Y') }}{{ $min->approver ? ' · ' . $min->approver->name : '' }}</x-nx-badge>
                                    @else
                                        <x-nx-badge variant="warning" dot>Entwurf</x-nx-badge>
                                    @endif
                                    @if($canEdit)
                                        <div class="flex gap-2">
                                            <x-nx-button variant="ghost" size="sm" wire:click="openMinutes({{ $min->id }})">Bearbeiten</x-nx-button>
                                            <x-nx-button size="sm" wire:click="toggleMinutesApproval({{ $min->id }})">{{ $min->approved_at ? 'Freigabe aufheben' : 'Freigeben' }}</x-nx-button>
                                            <x-nx-button variant="ghost" size="sm" icon wire:click="deleteMinutes({{ $min->id }})" wire:confirm="Protokoll löschen?" title="Löschen">@svg('heroicon-o-trash', 'w-4 h-4')</x-nx-button>
                                        </div>
                                    @endif
                                </div>
                                <div class="prose max-w-[68ch] text-[15px] leading-relaxed text-[color:var(--nx-text)]">
                                    {!! \Illuminate\Support\Str::markdown($min->content ?? '', ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
                                </div>
                            </x-nx-card>
                        @empty
                            <x-nx-card>
                                <div class="space-y-3 p-2">
                                    <p class="m-0 text-sm leading-relaxed text-[color:var(--nx-muted)]">Noch kein Protokoll. Beim Anlegen werden die Agenda-Punkte als Gliederung vorgeschlagen.</p>
                                    @if($canEdit)
                                        <x-nx-button size="sm" wire:click="openMinutes">Protokoll beginnen</x-nx-button>
                                    @endif
                                </div>
                            </x-nx-card>
                        @endforelse
                    </section>
                </div>

                {{-- Seitenspalte: Teilnehmer --}}
                <div class="space-y-8 self-start xl:sticky xl:top-6">
                    <section class="space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="m-0 text-sm font-semibold text-[color:var(--nx-text)]">Teilnehmer <span class="font-normal text-[color:var(--nx-faint)]">{{ $meeting->attendees->count() }}</span></h2>
                            @if($canEdit)
                                <x-nx-button variant="ghost" size="sm" icon wire:click="openAttendee" title="Teilnehmer hinzufügen">@svg('heroicon-o-plus', 'w-4 h-4')</x-nx-button>
                            @endif
                        </div>
                        <x-nx-card flush>
                            @forelse($meeting->attendees as $a)
                                <div class="group flex items-center gap-3 border-b border-[color:var(--nx-line)] px-4 py-2.5 last:border-b-0" wire:key="att-{{ $a->id }}">
                                    <button type="button" @if($canEdit) wire:click="toggleAttended({{ $a->id }})" @endif
                                            title="{{ $a->attended ? 'anwesend' : 'nicht anwesend' }}"
                                            class="shrink-0 {{ $a->attended ? 'text-[color:var(--nx-success)]' : 'text-[color:var(--nx-faint)]' }} {{ $canEdit ? 'hover:text-[color:var(--nx-text)]' : 'cursor-default' }}">
                                        @svg($a->attended ? 'heroicon-s-check-circle' : 'heroicon-o-minus-circle', 'w-5 h-5')
                                    </button>
                                    <span class="min-w-0 flex-1 truncate text-sm">
                                        {{ $a->user?->name ?? $a->agent?->name ?? '–' }}
                                        @if($a->agent_id)<span class="text-xs text-[color:var(--nx-faint)]">Agent</span>@endif
                                    </span>
                                    @if($a->role)<span class="text-xs text-[color:var(--nx-muted)]">{{ \Platform\Gathering\Enums\AttendeeRole::labelFor($a->role) }}</span>@endif
                                    @if($canEdit)
                                        <button type="button" wire:click="removeAttendee({{ $a->id }})" class="opacity-0 transition-opacity group-hover:opacity-100 text-[color:var(--nx-muted)] hover:text-[color:var(--nx-danger)]" title="Entfernen">@svg('heroicon-o-x-mark', 'w-4 h-4')</button>
                                    @endif
                                </div>
                            @empty
                                <p class="m-0 px-4 py-4 text-sm leading-relaxed text-[color:var(--nx-muted)]">Noch keine Teilnehmer. Anwesenheit hakst du nach dem Meeting ab.</p>
                            @endforelse
                        </x-nx-card>
                    </section>
                </div>
            </div>
        </div>
    </x-ui-page-container>

    @include('gathering::livewire.meeting.partials.modals')
</x-ui-page>
