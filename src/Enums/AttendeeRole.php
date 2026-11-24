<?php

namespace Platform\Gathering\Enums;

/** Host-agnostische Rollen. Das Feld ist frei schreibbar; dieser Enum dient nur Labels/Vorgaben. */
enum AttendeeRole: string
{
    case CHAIR = 'chair';
    case MEMBER = 'member';
    case GUEST = 'guest';
    case SECRETARY = 'secretary';

    public function label(): string
    {
        return match ($this) {
            self::CHAIR => 'Leitung',
            self::MEMBER => 'Teilnehmer',
            self::GUEST => 'Gast',
            self::SECRETARY => 'Protokoll',
        };
    }

    public static function labelFor(?string $role): ?string
    {
        if ($role === null || $role === '') {
            return null;
        }

        return self::tryFrom($role)?->label() ?? $role;
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
