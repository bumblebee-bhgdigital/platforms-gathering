<?php

/**
 * Gathering Web Routes
 *
 * Das Meeting ist das Ding. Die Workbench (Show) ist die Seite, auf der man landet,
 * wenn man ein Meeting öffnet — kein Hub, kein zweiter Eingang.
 *
 * @see Platform\Core\Routing\ModuleRouter für Details
 */

use Platform\Gathering\Livewire\Dashboard;
use Platform\Gathering\Livewire\Meeting\Show as MeetingShow;
use Platform\Gathering\Livewire\Showcase;

/** Übersicht aller Meetings des Teams. */
Route::get('/', Dashboard::class)->name('gathering.dashboard');

/** Workbench eines Meetings (Agenda, Beschlüsse, Protokoll, Teilnehmer). */
Route::get('/meetings/{meeting}', MeetingShow::class)->name('gathering.meetings.show');

/** nx-Showcase: lebendes Verzeichnis aller x-nx-* Komponenten (Design-Referenz). */
Route::get('/showcase', Showcase::class)->name('gathering.showcase');
