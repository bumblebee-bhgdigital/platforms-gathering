<?php

namespace Platform\Gathering\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\Core\Contracts\HasDisplayName;
use Platform\Gathering\Enums\MeetingStatus;
use Symfony\Component\Uid\UuidV7;

/**
 * Das Meeting ist das Ding. Alles andere hängt optional dran.
 *
 * - HOST (worum geht's): host() morphTo, host-los erlaubt. Projekt/Person/Gremium/Deal …
 * - TERMIN: scheduled_at + inbox_item_id (erscheint im Eingang, jump-to-source).
 * - Körper (optional): agendaItems, decisions, minutes, attendees.
 *
 * Reine Datenhaltung, keine Automatik, keine Serien-Logik (eine „Serie" ist die Meeting-Liste eines Hosts).
 *
 * @property-read string $uuid
 */
class Meeting extends Model implements HasDisplayName
{
    use SoftDeletes;

    protected $table = 'gathering_meetings';

    protected $fillable = [
        'uuid',
        'team_id',
        'host_type',
        'host_id',
        'title',
        'scheduled_at',
        'duration_minutes',
        'location',
        'inbox_item_id',
        'external_calendar_ref',
        'status',
        'created_by',
    ];

    protected $casts = [
        'uuid' => 'string',
        'scheduled_at' => 'datetime',
        'duration_minutes' => 'integer',
        'host_id' => 'integer',
        'inbox_item_id' => 'integer',
        'status' => MeetingStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                do {
                    $uuid = UuidV7::generate();
                } while (self::withTrashed()->where('uuid', $uuid)->exists());
                $model->uuid = $uuid;
            }
            if (empty($model->status)) {
                $model->status = MeetingStatus::PLANNED;
            }
            // Leerer Host wird konsequent null (host-los erlaubt).
            if ($model->host_type === '' || $model->host_id === null || $model->host_id === 0) {
                $model->host_type = $model->host_type ?: null;
            }
            if (empty($model->host_type)) {
                $model->host_type = null;
                $model->host_id = null;
            }
        });
    }

    /** Worum geht's — polymorph & lose. Null, wenn (noch) host-los. */
    public function host(): MorphTo
    {
        return $this->morphTo('host', 'host_type', 'host_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by');
    }

    public function agendaItems(): HasMany
    {
        return $this->hasMany(AgendaItem::class, 'meeting_id')->orderBy('position')->orderBy('id');
    }

    public function minutes(): HasMany
    {
        return $this->hasMany(Minutes::class, 'meeting_id');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class, 'meeting_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class, 'meeting_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->title;
    }

    /** Anzeigename des Hosts — tolerant: fehlt das Host-Modul, bleibt es ein kurzes Label. */
    public function hostName(): ?string
    {
        if (empty($this->host_type) || empty($this->host_id)) {
            return null;
        }
        try {
            $host = $this->host;
            if ($host instanceof HasDisplayName) {
                return $host->getDisplayName();
            }

            return $host?->title ?? $host?->name ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function scopeForHost(Builder $query, string $hostType, int $hostId): Builder
    {
        return $query->where('host_type', $hostType)->where('host_id', $hostId);
    }

    public function scopeStatus(Builder $query, MeetingStatus|string $status): Builder
    {
        $value = $status instanceof MeetingStatus ? $status->value : $status;

        return $query->where($query->getModel()->getTable() . '.status', $value);
    }

    /** Anstehend: ab jetzt terminiert, nicht abgesagt/durchgeführt. */
    public function scopeUpcoming(Builder $query): Builder
    {
        $table = $query->getModel()->getTable();

        return $query->whereNotNull($table . '.scheduled_at')
            ->where($table . '.scheduled_at', '>=', now())
            ->whereNotIn($table . '.status', [MeetingStatus::CANCELLED->value, MeetingStatus::HELD->value]);
    }
}
