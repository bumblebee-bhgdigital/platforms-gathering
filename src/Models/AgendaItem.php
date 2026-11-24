<?php

namespace Platform\Gathering\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\Core\Contracts\HasDisplayName;
use Platform\Gathering\Models\Concerns\BelongsToMeeting;
use Platform\Gathering\Models\Concerns\GeneratesUuid;

/**
 * Agenda-Punkt eines Meetings. Reine Datenhaltung — der Nutzer entscheidet, was auf die Agenda kommt.
 *
 * @property-read string $uuid
 */
class AgendaItem extends Model implements HasDisplayName
{
    use SoftDeletes, GeneratesUuid, BelongsToMeeting;

    protected $table = 'gathering_agenda_items';

    protected $fillable = [
        'uuid',
        'team_id',
        'meeting_id',
        'position',
        'title',
        'description',
        'owner_user_id',
        'owner_agent_id',
        'duration_minutes',
        'created_by',
    ];

    protected $casts = [
        'uuid' => 'string',
        'position' => 'integer',
        'duration_minutes' => 'integer',
    ];

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'owner_user_id');
    }

    public function ownerAgent(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'owner_agent_id');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class, 'agenda_item_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->title;
    }
}
