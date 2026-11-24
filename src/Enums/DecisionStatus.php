<?php

namespace Platform\Gathering\Enums;

enum DecisionStatus: string
{
    case OPEN = 'open';
    case IMPLEMENTED = 'implemented';
    case DISCARDED = 'discarded';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Offen',
            self::IMPLEMENTED => 'Umgesetzt',
            self::DISCARDED => 'Verworfen',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::IMPLEMENTED => 'success',
            self::DISCARDED => 'neutral',
            default => 'warning',
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
