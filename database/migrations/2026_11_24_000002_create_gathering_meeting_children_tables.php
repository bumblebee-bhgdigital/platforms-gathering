<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kinder eines Meetings: Agenda-Punkte, Beschlüsse, Protokoll, Teilnehmer.
 * Reine Datenhaltung — keine Standard-Agenda, keine Ablaufregeln, keine Automatik.
 * team_id wird aus dem Meeting abgeleitet. Owner/Teilnehmer: entweder *_user_id oder *_agent_id
 * (Agent = User vom Typ ai_user). linked_task_id verweist lose auf eine Planner-Aufgabe (keine FK).
 *
 * Bewusst host-agnostisch und OHNE Lenkungsausschuss-Leitfaden — die LA-Felder sind ein
 * management-book-Aufsatz, kein Teil des geteilten Körpers.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gathering_agenda_items')) {
            Schema::create('gathering_agenda_items', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
                $table->foreignId('meeting_id')->constrained('gathering_meetings')->cascadeOnDelete();

                $table->unsignedInteger('position')->default(0);
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('owner_agent_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('duration_minutes')->nullable();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['meeting_id', 'position'], 'gathering_agenda_meeting_pos_idx');
                $table->index('team_id');
            });
        }

        if (! Schema::hasTable('gathering_minutes')) {
            Schema::create('gathering_minutes', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
                $table->foreignId('meeting_id')->constrained('gathering_meetings')->cascadeOnDelete();

                $table->longText('content')->nullable(); // Markdown
                $table->dateTime('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index('meeting_id');
                $table->index('team_id');
            });
        }

        if (! Schema::hasTable('gathering_decisions')) {
            Schema::create('gathering_decisions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
                $table->foreignId('meeting_id')->constrained('gathering_meetings')->cascadeOnDelete();
                $table->foreignId('agenda_item_id')->nullable()->constrained('gathering_agenda_items')->nullOnDelete();

                $table->text('text');
                $table->string('status', 20)->default('open'); // open | implemented | discarded
                $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('owner_agent_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('due_date')->nullable();
                $table->unsignedBigInteger('linked_task_id')->nullable(); // lose Planner-Aufgabe

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index('meeting_id');
                $table->index('agenda_item_id');
                $table->index(['team_id', 'status']);
                $table->index('linked_task_id');
            });
        }

        if (! Schema::hasTable('gathering_attendees')) {
            Schema::create('gathering_attendees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
                $table->foreignId('meeting_id')->constrained('gathering_meetings')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->foreignId('agent_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->string('role', 50)->nullable(); // chair | member | guest | secretary (frei)
                $table->boolean('attended')->default(false);
                $table->timestamps();

                $table->unique(['meeting_id', 'user_id'], 'gathering_attendee_meeting_user_uq');
                $table->unique(['meeting_id', 'agent_id'], 'gathering_attendee_meeting_agent_uq');
                $table->index('team_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gathering_attendees');
        Schema::dropIfExists('gathering_decisions');
        Schema::dropIfExists('gathering_minutes');
        Schema::dropIfExists('gathering_agenda_items');
    }
};
