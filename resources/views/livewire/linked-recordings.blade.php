{{--
    Aufnahmen (Whisper), die direkt am Meeting hängen. Nur sichtbar, wenn das Whisper-Modul installiert ist.
    Deterministisch: hier angehängt, nicht per Zeit-Overlap geraten.
--}}
<div>
    @if($available)
        @php
            $statusBadge = fn ($s) => match ($s) {
                'completed' => ['success', 'Fertig'],
                'processing' => ['info', 'Läuft'],
                'pending' => ['warning', 'Wartet'],
                'failed' => ['danger', 'Fehler'],
                default => ['neutral', $s],
            };
        @endphp
        <section class="space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="m-0 {{ $compact ? 'text-sm' : 'text-base' }} font-semibold text-[color:var(--nx-text)]">
                        Aufnahmen <span class="font-normal text-[color:var(--nx-faint)]">{{ $recordings->count() }}</span>
                    </h2>
                    @unless($compact)
                        <p class="mt-1 mb-0 max-w-[70ch] text-sm leading-relaxed text-[color:var(--nx-muted)]">
                            Hänge die Aufnahme des Meetings direkt hier an. Das Transkript kannst du als Protokoll-Entwurf übernehmen.
                        </p>
                    @endunless
                </div>
                @if($canEdit)
                    <x-nx-button variant="secondary" size="sm" wire:click="openLink">@svg('heroicon-o-microphone', 'w-4 h-4') Aufnahme anhängen</x-nx-button>
                @endif
            </div>

            @if($recordings->isEmpty())
                <div class="rounded-[8px] border border-dashed border-[color:var(--nx-line-strong)] px-5 py-6 text-sm leading-relaxed text-[color:var(--nx-muted)]">
                    Noch keine Aufnahme. Hänge eine vorhandene Team-Aufnahme an dieses Meeting.
                </div>
            @else
                <x-nx-card flush>
                    @foreach($recordings as $rec)
                        @php [$bv, $bl] = $statusBadge($rec->status); @endphp
                        <div class="group flex items-start gap-3 border-b border-[color:var(--nx-line)] px-5 py-3.5 last:border-b-0" wire:key="rec-{{ $rec->id }}">
                            @svg('heroicon-o-microphone', 'mt-0.5 w-5 h-5 shrink-0 text-[color:var(--nx-faint)]')
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="truncate text-sm font-medium text-[color:var(--nx-text)]">{{ $rec->title ?: 'Aufnahme #' . $rec->id }}</span>
                                    <x-nx-badge :variant="$bv" dot>{{ $bl }}</x-nx-badge>
                                </div>
                                <div class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-[color:var(--nx-muted)]">
                                    @if($rec->recorded_at)<span>{{ $rec->recorded_at->translatedFormat('d.m.Y · H:i') }}</span>@endif
                                    @if($rec->duration_seconds)<span>{{ gmdate($rec->duration_seconds >= 3600 ? 'H:i:s' : 'i:s', (int) $rec->duration_seconds) }} Min.</span>@endif
                                    @if(filled($rec->transcript))<span>Transkript vorhanden</span>@endif
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                @if($routes['show'])
                                    <x-nx-button variant="ghost" size="sm" :href="route('whisper.recordings.show', $rec)">Öffnen</x-nx-button>
                                @endif
                                @if($canEdit && $isMeeting && filled($rec->transcript))
                                    <x-nx-button variant="ghost" size="sm" wire:click="toMinutes({{ $rec->id }})"
                                                 wire:confirm="Transkript als neuen Protokoll-Entwurf anlegen?" title="Transkript → Protokoll">
                                        @svg('heroicon-o-document-text', 'w-4 h-4') Protokoll
                                    </x-nx-button>
                                @endif
                                @if($canEdit)
                                    <button type="button" wire:click="detachRecording({{ $rec->id }})"
                                            wire:confirm="Verknüpfung lösen? Die Aufnahme selbst bleibt erhalten."
                                            class="opacity-0 transition-opacity group-hover:opacity-100 text-[color:var(--nx-muted)] hover:text-[color:var(--nx-danger)]" title="Verknüpfung lösen">@svg('heroicon-o-link-slash', 'w-4 h-4')</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </x-nx-card>
            @endif
        </section>

        {{-- Aufnahme anhängen --}}
        <x-nx-modal wire:model="showLink" size="md">
            <x-slot name="header">Aufnahme anhängen</x-slot>
            <div class="space-y-3">
                <x-nx-input-text name="linkSearch" label="Suche" placeholder="Titel …" wire:model.live.debounce.300ms="linkSearch" />
                <div class="divide-y divide-[color:var(--nx-line)] rounded-[8px] border border-[color:var(--nx-line)]">
                    @forelse($candidates as $c)
                        <button type="button" wire:key="reccand-{{ $c->id }}" wire:click="attachRecording({{ $c->id }})"
                                class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm hover:bg-[color:var(--nx-hover)]">
                            @svg('heroicon-o-microphone', 'w-4 h-4 shrink-0 text-[color:var(--nx-faint)]')
                            <span class="min-w-0 flex-1 truncate">{{ $c->title ?: 'Aufnahme #' . $c->id }}</span>
                            <span class="text-xs text-[color:var(--nx-faint)]">{{ $c->recorded_at?->format('d.m.Y') }}</span>
                        </button>
                    @empty
                        <p class="m-0 px-4 py-4 text-sm text-[color:var(--nx-muted)]">Keine passenden Aufnahmen im Team.</p>
                    @endforelse
                </div>
            </div>
            <x-slot name="footer">
                <x-nx-button variant="ghost" wire:click="$set('showLink', false)">Schließen</x-nx-button>
            </x-slot>
        </x-nx-modal>
    @endif
</div>
