<?php

namespace Platform\Gathering\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\Core\Contracts\HasDisplayName;
use Platform\Gathering\Models\Concerns\BelongsToMeeting;
use Platform\Gathering\Models\Concerns\GeneratesUuid;

/**
 * Protokoll eines Meetings (Markdown). Freigabe über approved_at/approved_by, keine Automatik.
 *
 * @property-read string $uuid
 */
class Minutes extends Model implements HasDisplayName
{
    use SoftDeletes, GeneratesUuid, BelongsToMeeting;

    protected $table = 'gathering_minutes';

    protected $fillable = [
        'uuid',
        'team_id',
        'meeting_id',
        'content',
        'approved_at',
        'approved_by',
        'created_by',
    ];

    protected $casts = [
        'uuid' => 'string',
        'approved_at' => 'datetime',
    ];

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'approved_by');
    }

    public function getDisplayName(): ?string
    {
        return 'Protokoll #' . $this->id;
    }
}
