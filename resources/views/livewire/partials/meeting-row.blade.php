{{-- Eine Meeting-Zeile (klickbar = Workbench). Erwartet $m (Meeting, mit *_count). --}}
@php
    $when = $m->scheduled_at?->translatedFormat('D, d.m.Y · H:i');
    $origin = \Platform\Gathering\Support\HostChip::for($m);
@endphp
<a href="{{ route('gathering.meetings.show', $m) }}" wire:navigate
   class="group flex items-center gap-3 border-b border-[color:var(--nx-line)] px-5 py-3.5 last:border-b-0 transition-colors hover:bg-[color:var(--nx-hover)]"
   wire:key="meeting-{{ $m->id }}">
    @svg('heroicon-o-calendar-days', 'w-5 h-5 shrink-0 text-[color:var(--nx-faint)]')
    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
            <span class="truncate text-sm font-medium text-[color:var(--nx-text)]">{{ $m->title }}</span>
            <x-nx-badge :variant="$m->status?->badge() ?? 'neutral'" dot>{{ $m->status?->label() }}</x-nx-badge>
        </div>
        <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-[color:var(--nx-muted)]">
            <span>{{ $when ?? 'kein Termin' }}</span>
            @if($origin)<span class="inline-flex items-center gap-1" title="gehört zu {{ $origin['type'] }}{{ $origin['name'] ? ' · '.$origin['name'] : '' }}">@svg($origin['icon'], 'w-3 h-3 text-[color:var(--nx-faint)]') {{ $origin['type'] }}@if($origin['name']) · {{ $origin['name'] }}@endif</span>@endif
            @if(($m->agenda_items_count ?? 0) > 0)<span>{{ $m->agenda_items_count }} Agenda</span>@endif
            @if(($m->decisions_count ?? 0) > 0)<span>{{ $m->decisions_count }} Beschlüsse</span>@endif
            @if(($m->attendees_count ?? 0) > 0)<span>{{ $m->attendees_count }} Teilnehmer</span>@endif
        </div>
    </div>
    @svg('heroicon-o-chevron-right', 'w-4 h-4 shrink-0 text-[color:var(--nx-faint)] opacity-0 transition-opacity group-hover:opacity-100')
</a>
