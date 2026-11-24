<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gathering — das Meeting ist das Ding. Alles andere hängt optional dran.
 *
 * Zwei Achsen (siehe Modell): HOST (worum geht's — Projekt/Person/Gremium, polymorph & lose,
 * host-los erlaubt) und TERMIN (inbox_item_id → erscheint im Eingang, jump-to-source).
 * external_calendar_ref = lose Referenz auf einen MS365-Termin (zwei Gesichter, ein Termin).
 * Bewusst KEIN Serien-Objekt: eine „Serie" ist die Meeting-Liste eines Hosts.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gathering_meetings')) {
            return;
        }

        Schema::create('gathering_meetings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();

            // HOST (polymorph, lose, ohne FK — host-los erlaubt): Projekt, Person, Gremium, Deal, …
            // host_type = Morph-Alias (z. B. 'project'), vom Host-Modul in der morphMap registriert.
            $table->string('host_type', 100)->nullable();
            $table->unsignedBigInteger('host_id')->nullable();

            $table->string('title');
            $table->dateTime('scheduled_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('location')->nullable();

            // TERMIN-Anker: das Inbox-Item (Kanal meeting), über das das Meeting im Eingang erscheint. Lose, ohne FK.
            $table->unsignedBigInteger('inbox_item_id')->nullable();
            // Lose Referenz auf einen bestehenden MS365-Kalendertermin (keine Serien-Logik, keine RRULE).
            $table->string('external_calendar_ref')->nullable();

            // planned | prepared | held | cancelled
            $table->string('status', 20)->default('planned');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index(['host_type', 'host_id']);
            $table->index(['team_id', 'scheduled_at']);
            $table->index('inbox_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gathering_meetings');
    }
};
