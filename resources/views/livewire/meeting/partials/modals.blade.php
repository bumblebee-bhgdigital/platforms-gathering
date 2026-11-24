{{-- Modals der Meeting-Workbench. Immer innerhalb von <x-ui-page>. --}}

{{-- Meeting bearbeiten --}}
<x-nx-modal wire:model="showMeetingEdit" size="md">
    <x-slot name="header">Meeting bearbeiten</x-slot>
    <div class="space-y-4">
        <x-nx-input-text name="meetingForm.title" label="Titel" wire:model="meetingForm.title" required />
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-[color:var(--nx-text)]">Termin</label>
                <input type="datetime-local" wire:model="meetingForm.scheduled_at"
                       class="w-full rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm text-[color:var(--nx-text)]" />
            </div>
            <x-nx-input-number name="meetingForm.duration_minutes" label="Dauer (Min.)" wire:model="meetingForm.duration_minutes" />
        </div>
        <x-nx-input-text name="meetingForm.location" label="Ort / Link" wire:model="meetingForm.location" />
    </div>
    <x-slot name="footer">
        <div class="flex justify-end gap-3">
            <x-nx-button variant="ghost" wire:click="$set('showMeetingEdit', false)">Abbrechen</x-nx-button>
            <x-nx-button variant="primary" wire:click="saveMeeting">Speichern</x-nx-button>
        </div>
    </x-slot>
</x-nx-modal>

{{-- Agenda-Punkt --}}
<x-nx-modal wire:model="showItem" size="md">
    <x-slot name="header">{{ $editingItemId ? 'Agenda-Punkt bearbeiten' : 'Agenda-Punkt' }}</x-slot>
    <div class="space-y-4">
        <x-nx-input-text name="itemForm.title" label="Titel" wire:model="itemForm.title" required />
        <x-nx-input-textarea name="itemForm.description" label="Beschreibung" wire:model="itemForm.description" rows="3" />
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-nx-input-select name="itemForm.owner_user_id" label="Verantwortlich" wire:model="itemForm.owner_user_id">
                <option value="">—</option>
                @foreach($userOptions as $u)
                    <option value="{{ $u['value'] }}">{{ $u['label'] }}</option>
                @endforeach
            </x-nx-input-select>
            <x-nx-input-number name="itemForm.duration_minutes" label="Dauer (Min.)" wire:model="itemForm.duration_minutes" />
        </div>
    </div>
    <x-slot name="footer">
        <div class="flex justify-end gap-3">
            <x-nx-button variant="ghost" wire:click="$set('showItem', false)">Abbrechen</x-nx-button>
            <x-nx-button variant="primary" wire:click="saveItem">Speichern</x-nx-button>
        </div>
    </x-slot>
</x-nx-modal>

{{-- Beschluss --}}
<x-nx-modal wire:model="showDecision" size="md">
    <x-slot name="header">{{ $editingDecisionId ? 'Beschluss bearbeiten' : 'Beschluss' }}</x-slot>
    <div class="space-y-4">
        <x-nx-input-textarea name="decisionForm.text" label="Beschluss" wire:model="decisionForm.text" rows="3" required />
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-nx-input-select name="decisionForm.status" label="Status" wire:model="decisionForm.status">
                @foreach($decisionStatusOptions as $o)
                    <option value="{{ $o['value'] }}">{{ $o['label'] }}</option>
                @endforeach
            </x-nx-input-select>
            <x-nx-input-select name="decisionForm.owner_user_id" label="Verantwortlich" wire:model="decisionForm.owner_user_id">
                <option value="">—</option>
                @foreach($userOptions as $u)
                    <option value="{{ $u['value'] }}">{{ $u['label'] }}</option>
                @endforeach
            </x-nx-input-select>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-nx-input-date name="decisionForm.due_date" label="Fällig" wire:model="decisionForm.due_date" />
            <x-nx-input-select name="decisionForm.agenda_item_id" label="Zu Agenda-Punkt" wire:model="decisionForm.agenda_item_id">
                <option value="">—</option>
                @foreach($itemOptions as $o)
                    <option value="{{ $o['value'] }}">{{ $o['label'] }}</option>
                @endforeach
            </x-nx-input-select>
        </div>
    </div>
    <x-slot name="footer">
        <div class="flex justify-end gap-3">
            <x-nx-button variant="ghost" wire:click="$set('showDecision', false)">Abbrechen</x-nx-button>
            <x-nx-button variant="primary" wire:click="saveDecision">Speichern</x-nx-button>
        </div>
    </x-slot>
</x-nx-modal>

{{-- Teilnehmer --}}
<x-nx-modal wire:model="showAttendee" size="sm">
    <x-slot name="header">Teilnehmer hinzufügen</x-slot>
    <div class="space-y-4">
        <x-nx-input-select name="attendeeForm.user_id" label="Person" wire:model="attendeeForm.user_id" required>
            <option value="">—</option>
            @foreach($userOptions as $u)
                <option value="{{ $u['value'] }}">{{ $u['label'] }}</option>
            @endforeach
        </x-nx-input-select>
        <x-nx-input-select name="attendeeForm.role" label="Rolle" wire:model="attendeeForm.role">
            @foreach($roleOptions as $r)
                <option value="{{ $r['value'] }}">{{ $r['label'] }}</option>
            @endforeach
        </x-nx-input-select>
    </div>
    <x-slot name="footer">
        <div class="flex justify-end gap-3">
            <x-nx-button variant="ghost" wire:click="$set('showAttendee', false)">Abbrechen</x-nx-button>
            <x-nx-button variant="primary" wire:click="addAttendee">Hinzufügen</x-nx-button>
        </div>
    </x-slot>
</x-nx-modal>

{{-- Protokoll --}}
<x-nx-modal wire:model="showMinutes" size="lg">
    <x-slot name="header">{{ $editingMinutesId ? 'Protokoll bearbeiten' : 'Protokoll' }}</x-slot>
    <div class="space-y-2">
        <x-nx-input-textarea name="minutesForm.content" label="Inhalt (Markdown)" wire:model="minutesForm.content" rows="14" />
        <p class="m-0 text-xs text-[color:var(--nx-faint)]">Markdown wird beim Anzeigen gerendert.</p>
    </div>
    <x-slot name="footer">
        <div class="flex justify-end gap-3">
            <x-nx-button variant="ghost" wire:click="$set('showMinutes', false)">Abbrechen</x-nx-button>
            <x-nx-button variant="primary" wire:click="saveMinutes">Speichern</x-nx-button>
        </div>
    </x-slot>
</x-nx-modal>
