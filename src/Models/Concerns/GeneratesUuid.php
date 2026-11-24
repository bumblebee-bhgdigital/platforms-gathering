<?php

namespace Platform\Gathering\Models\Concerns;

use Symfony\Component\Uid\UuidV7;

/** Vergibt beim Erstellen eine UUIDv7. */
trait GeneratesUuid
{
    protected static function generateUniqueUuid(): string
    {
        $query = method_exists(static::class, 'bootSoftDeletes') ? static::withTrashed() : static::query();
        do {
            $uuid = UuidV7::generate();
        } while ((clone $query)->where('uuid', $uuid)->exists());

        return $uuid;
    }

    public static function bootGeneratesUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = static::generateUniqueUuid();
            }
        });
    }
}
