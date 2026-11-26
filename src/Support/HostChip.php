<?php

namespace Platform\Gathering\Support;

use Illuminate\Support\Str;
use Platform\Core\Contracts\BriefsMeetings;
use Platform\Core\Contracts\MeetingHost;
use Platform\Core\Support\MeetingBriefing;
use Platform\Gathering\Models\Meeting;

/**
 * Herkunft eines Meetings als anzeigefertiger Chip (Typ-Icon + Typ-Label + Host-Name
 * + Link zur Quelle) und — falls der Host ihn stellt — sein Briefing.
 *
 * Rein vertragsgetrieben: der Host liefert Icon/URL/Label selbst über
 * {@see MeetingHost}, das Briefing über {@see BriefsMeetings}. gathering importiert
 * KEINE Host-Klassen und kennt keine Host-Routen — fehlt der Vertrag, degradiert der
 * Chip auf ein generisches Icon ohne Link. Lose, wie das ganze Modell.
 *
 * @phpstan-type Origin array{icon: string, type: string, name: ?string, url: ?string}
 */
class HostChip
{
    /** @return Origin|null  Null, wenn das Meeting (noch) host-los ist. */
    public static function for(Meeting $meeting): ?array
    {
        if (empty($meeting->host_type) || empty($meeting->host_id)) {
            return null;
        }

        $host = self::resolveHost($meeting);

        $icon = $host instanceof MeetingHost ? $host->meetingHostIcon() : null;
        $url = $host instanceof MeetingHost ? $host->meetingHostUrl() : null;
        $type = $host instanceof MeetingHost ? $host->meetingHostTypeLabel() : null;

        return [
            'icon' => 'heroicon-o-' . ($icon ?: 'link'),
            'type' => $type ?: self::typeLabel((string) $meeting->host_type),
            'name' => $meeting->hostName(),
            'url' => $url,
        ];
    }

    /** Die Anforderung, die der Host an dieses Meeting stellt (z. B. LA-Leitfaden); null, wenn keiner. */
    public static function briefingFor(Meeting $meeting): ?MeetingBriefing
    {
        if (empty($meeting->host_type) || empty($meeting->host_id)) {
            return null;
        }

        $host = self::resolveHost($meeting);
        if (! $host instanceof BriefsMeetings) {
            return null;
        }

        try {
            return $host->meetingBriefing();
        } catch (\Throwable) {
            return null; // ein Briefing darf die Werkbank nie kippen
        }
    }

    /** Host-Modell tolerant laden (fehlt das Host-Modul, bleibt es null). */
    protected static function resolveHost(Meeting $meeting): ?object
    {
        try {
            return $meeting->host;
        } catch (\Throwable) {
            return null;
        }
    }

    /** „Projekt" statt „project" — über die ObjectTypeRegistry, falls vorhanden; sonst humanisiert. */
    protected static function typeLabel(string $alias): string
    {
        $registry = \Platform\Organization\Services\ObjectTypeRegistry::class;
        if (class_exists($registry)) {
            try {
                if ($label = $registry::label($alias, true) ?? $registry::label($alias)) {
                    return $label;
                }
            } catch (\Throwable) {
            }
        }

        return Str::headline($alias);
    }
}
