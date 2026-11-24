<?php

namespace Platform\Gathering\Policies;

use Illuminate\Support\Facades\DB;
use Platform\Core\Models\User;
use Platform\Gathering\Models\Meeting;

/**
 * Rechte am Meeting: wer zum Team des Meetings gehört, darf sehen und bearbeiten.
 * Die Kinder (Agenda/Beschlüsse/Protokoll/Teilnehmer) erben diese Rechte über das Meeting.
 * Bewusst einfach — feinere Host-abgeleitete Rechte kommen, wenn ein Host das braucht.
 */
class MeetingPolicy
{
    public function view(User $user, Meeting $meeting): bool
    {
        return $this->memberOfTeam($user, (int) $meeting->team_id);
    }

    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(User $user, Meeting $meeting): bool
    {
        return $this->memberOfTeam($user, (int) $meeting->team_id);
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $this->memberOfTeam($user, (int) $meeting->team_id);
    }

    protected function memberOfTeam(User $user, int $teamId): bool
    {
        if ($teamId <= 0) {
            return false;
        }
        if ((int) ($user->current_team_id ?? $user->currentTeam?->id) === $teamId) {
            return true;
        }

        return DB::table('team_user')
            ->where('team_id', $teamId)
            ->where('user_id', $user->id)
            ->exists();
    }
}
