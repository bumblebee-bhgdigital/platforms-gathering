<?php

/**
 * Gathering Web Routes
 * 
 * Diese Datei definiert alle Web-Routes für das Modul.
 * 
 * WICHTIG FÜR LLMs:
 * - Routes werden automatisch mit dem Modul-Prefix versehen (aus Config)
 * - Middleware wird automatisch hinzugefügt (web, auth, etc.)
 * - Route-Namen sollten mit dem Modul-Prefix beginnen
 * 
 * BEISPIEL:
 * Route::get('/', Dashboard::class)->name('gathering.dashboard');
 * 
 * Wird zu: /gathering/ (wenn prefix = 'gathering')
 * 
 * @see Platform\Core\Routing\ModuleRouter für Details
 */

use Platform\Gathering\Livewire\Dashboard;
use Platform\Gathering\Livewire\Showcase;

/**
 * Dashboard Route
 *
 * Hauptübersicht des Moduls
 */
Route::get('/', Dashboard::class)->name('gathering.dashboard');

/**
 * nx-Showcase Route
 *
 * Lebendes Verzeichnis aller x-nx-* Komponenten (Design-Referenz).
 */
Route::get('/showcase', Showcase::class)->name('gathering.showcase');

/**
 * Weitere Routes hinzufügen:
 *
 * Route::get('/entities', Entity\Index::class)->name('gathering.entities.index');
 * Route::get('/entities/{entity}', Entity\Show::class)->name('gathering.entities.show');
 */
