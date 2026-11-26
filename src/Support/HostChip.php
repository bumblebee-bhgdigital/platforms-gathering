<?php

namespace Platform\Gathering\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Platform\Gathering\Models\Meeting;

/**
 * Herkunft eines Meetings als anzeigefertiger Chip: Typ-Icon + Typ-Label + Host-Name,
 * optional Link zur Quelle (jump-to-source). „Woran hängt dieses Meeting?"
 *
 * INTERIM — bewusst klein und an EINER Stelle: Icon + Route kommen aus einer
 * Hinweis-Tabelle für die heute existierenden Hosts. gathering importiert KEINE
 * Host-Klassen (nur Morph-Alias-Strings + Route-Namen, `Route::has`-guarded);
 * unbekannte Hosts degradieren auf generisches Icon + keinen Link. Diese Tabelle
 * verschwindet, sobald der host-agnostische Core-Vertrag steht (der Host liefert
 * dann icon()/url() selbst). Bis dahin: sichtbare Herkunft, ohne Kopplung.
 *
 * @phpstan-type Origin array{icon: string, type: string, name: ?string, url: ?string}
 */
class HostChip
{
    /** Morph-Alias => [heroicon-Suffix, Route-Name|null]. Route wird mit dem Host-Modell aufgerufen. */
    protected const HINTS = [
        'project' => ['briefcase', 'planner.projects.show'],
        'management_book_committee' => ['user-group', 'management-book.committees.show'],
    ];

    /** @return Origin|null  Null, wenn das Meeting (noch) host-los ist. */
    public static function for(Meeting $meeting): ?array
    {
        if (empty($meeting->host_type) || empty($meeting->host_id)) {
            return null;
        }

        $alias = (string) $meeting->host_type;
        [$icon, $routeName] = self::HINTS[$alias] ?? ['link', null];

        $host = null;
        try {
            $host = $meeting->host; // tolerant: fehlt das Host-Modul, bleibt $host null
        } catch (\Throwable) {
        }

        $url = null;
        if ($host && $routeName && Route::has($routeName)) {
            try {
                $url = route($routeName, $host);
            } catch (\Throwable) {
            }
        }

        return [
            'icon' => 'heroicon-o-' . $icon,
            'type' => self::typeLabel($alias),
            'name' => $meeting->hostName(),
            'url' => $url,
        ];
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
