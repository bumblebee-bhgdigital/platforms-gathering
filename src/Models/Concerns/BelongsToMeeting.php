<?php

namespace Platform\Gathering\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Gathering\Models\Meeting;

/**
 * Gemeinsame Basis der Meeting-Kinder: team_id wird aus dem Meeting abgeleitet,
 * Rechte werden über das Meeting geerbt.
 */
trait BelongsToMeeting
{
    public static function bootBelongsToMeeting(): void
    {
        static::saving(function ($model) {
            $meeting = Meeting::withTrashed()->find($model->meeting_id);
            if (! $meeting) {
                throw new \InvalidArgumentException("meeting_id {$model->meeting_id} nicht gefunden.");
            }
            if (empty($model->team_id)) {
                $model->team_id = $meeting->team_id;
            }
        });
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }
}
