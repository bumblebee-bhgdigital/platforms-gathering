<?php

namespace Platform\Gathering\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\Core\Contracts\HasDisplayName;
use Platform\Gathering\Enums\DecisionStatus;
use Platform\Gathering\Models\Concerns\BelongsToMeeting;
use Platform\Gathering\Models\Concerns\GeneratesUuid;

/**
 * Beschluss eines Meetings (optional zu einem Agenda-Punkt) mit Status, Verantwortlichem und Fälligkeit.
 * linked_task_id verweist lose auf eine Planner-Aufgabe (keine Automatik).
 *
 * @property-read string $uuid
 */
class Decision extends Model implements HasDisplayName
{
    use SoftDeletes, GeneratesUuid, BelongsToMeeting;

    protected $table = 'gathering_decisions';

    protected $fillable = [
        'uuid',
        'team_id',
        'meeting_id',
        'agenda_item_id',
        'text',
        'status',
        'owner_user_id',
        'owner_agent_id',
        'due_date',
        'linked_task_id',
        'created_by',
    ];

    protected $casts = [
        'uuid' => 'string',
        'status' => DecisionStatus::class,
        'due_date' => 'date',
        'linked_task_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->status)) {
                $model->status = DecisionStatus::OPEN;
            }
        });
        static::saving(function (self $model) {
            if ($model->agenda_item_id) {
                $item = AgendaItem::withTrashed()->find($model->agenda_item_id);
                if (! $item || (int) $item->meeting_id !== (int) $model->meeting_id) {
                    throw new \InvalidArgumentException('agenda_item_id gehört nicht zu diesem Meeting.');
                }
            }
        });
    }

    public function agendaItem(): BelongsTo
    {
        return $this->belongsTo(AgendaItem::class, 'agenda_item_id');
    }

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'owner_user_id');
    }

    public function ownerAgent(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'owner_agent_id');
    }

    public function getDisplayName(): ?string
    {
        return \Illuminate\Support\Str::limit((string) $this->text, 80);
    }
}
