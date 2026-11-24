<?php

namespace Platform\Gathering\Enums;

enum MeetingStatus: string
{
    case PLANNED = 'planned';
    case PREPARED = 'prepared';
    case HELD = 'held';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PLANNED => 'Geplant',
            self::PREPARED => 'Vorbereitet',
            self::HELD => 'Durchgeführt',
            self::CANCELLED => 'Abgesagt',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::PLANNED => 'neutral',
            self::PREPARED => 'info',
            self::HELD => 'success',
            self::CANCELLED => 'neutral',
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
