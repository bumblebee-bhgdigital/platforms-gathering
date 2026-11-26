<?php

namespace Platform\Gathering\Terminal;

use Illuminate\Database\Eloquent\Relations\Relation;
use Platform\Core\Terminal\Apps\ContextBadges;
use Platform\Core\Terminal\Contracts\HasBadge;
use Platform\Core\Terminal\Contracts\TerminalApp;
use Platform\Core\Terminal\TerminalContext;
use Platform\Gathering\Models\Meeting;

/**
 * Reiter „Meetings" im Kontext-Panel: am aktuellen Datensatz (Projekt, Person, Gremium …)
 * hängende Meetings anzeigen und direkt in der Sidebar neue anlegen — gleiches Muster wie
 * Dateien/Zeit/Agenda. Sichtbar nur, wenn die Seite die App per page-context anbietet
 * (`'apps' => ['meetings' => true]` in pageContextOptions) — der Host entscheidet, nicht gathering.
 *
 * Badge = Anzahl Meetings an diesem Host.
 */
class MeetingsPanelApp implements TerminalApp, HasBadge
{
    public function key(): string
    {
        return 'meetings';
    }

    public function label(): string
    {
        return 'Meetings';
    }

    public function icon(): string
    {
        return 'calendar-days';
    }

    public function group(): string
    {
        return 'module';
    }

    public function order(): int
    {
        return 45;
    }

    public function livewireComponent(): string
    {
        return 'gathering.panel.meetings';
    }

    public function isAvailable(TerminalContext $context): bool
    {
        return $context->hasContext() && $context->offers('meetings');
    }

    public function badge(TerminalContext $context): ?string
    {
        $hostType = self::hostAliasFor($context->contextType);

        $count = Meeting::query()
            ->forHost($hostType, (int) $context->contextId)
            ->count();

        return ContextBadges::count($count);
    }

    /** FQCN des Seitenmodells → Morph-Alias, unter dem Meeting.host_type gespeichert wird (z. B. 'project'). */
    public static function hostAliasFor(?string $contextType): ?string
    {
        if (! $contextType) {
            return null;
        }

        $alias = array_search($contextType, Relation::morphMap(), true);

        return $alias !== false ? $alias : $contextType;
    }
}
