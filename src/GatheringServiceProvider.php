<?php

/**
 * Gathering Service Provider
 * 
 * Dieser Service Provider ist das Herzstück jedes Platform-Moduls.
 * 
 * WICHTIG FÜR LLMs:
 * - Dieser Service Provider folgt dem exakten Muster von HCM und Planner
 * - Alle wichtigen Schritte sind kommentiert
 * - Config wird in register() geladen (Laravel Best Practice)
 * - Modul-Registrierung erfolgt in boot()
 * 
 * ANPASSUNGEN FÜR NEUES MODUL:
 * 1. Ersetze "Gathering" durch deinen Modul-Namen (PascalCase)
 * 2. Ersetze "gathering" durch deinen Modul-Namen (kebab-case)
 * 3. Passe Namespaces an
 * 4. Füge Commands/Tools hinzu falls nötig
 * 
 * @see Platform\Core\PlatformCore für Modul-Registrierung
 * @see Platform\Core\Routing\ModuleRouter für Route-Registrierung
 */

namespace Platform\Gathering;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class GatheringServiceProvider extends ServiceProvider
{
    /**
     * Register Services
     * 
     * Wird VOR boot() aufgerufen.
     * Hier sollten nur leichte Registrierungen erfolgen.
     * 
     * LARAVEL BEST PRACTICE:
     * - Config sollte hier geladen werden (mergeConfigFrom)
     * - Commands können hier registriert werden
     */
    public function register(): void
    {
        /**
         * Config laden
         * 
         * mergeConfigFrom lädt die Config aus dem Package-Verzeichnis
         * und merged sie mit der Config aus config/ (falls vorhanden).
         * 
         * WICHTIG: Muss in register() sein, nicht in boot()!
         */
        $this->mergeConfigFrom(__DIR__.'/../config/gathering.php', 'gathering');
        
        /**
         * Commands registrieren (optional)
         * 
         * Falls dein Modul Artisan Commands hat:
         * 
         * if ($this->app->runningInConsole()) {
         *     $this->commands([
         *         \Platform\Gathering\Console\Commands\YourCommand::class,
         *     ]);
         * }
         */
    }

    /**
     * Boot Services
     * 
     * Wird NACH register() aufgerufen.
     * Hier erfolgt die eigentliche Modul-Registrierung.
     * 
     * REIHENFOLGE IST WICHTIG:
     * 1. Config prüfen (bereits in register() geladen)
     * 2. Modul bei PlatformCore registrieren
     * 3. Routes laden (nur wenn Modul registriert)
     * 4. Migrationen, Views, Livewire registrieren
     */
    public function boot(): void
    {
        /**
         * SCHRITT 1: Modul-Registrierung prüfen
         * 
         * Prüft ob:
         * - Config vorhanden ist
         * - modules-Tabelle existiert (für Datenbank-Registrierung)
         * 
         * Nur wenn beide Bedingungen erfüllt, wird das Modul registriert.
         */
        if (
            config()->has('gathering.routing') &&
            config()->has('gathering.navigation') &&
            PlatformCore::modulesTableExists()
        ) {
            /**
             * Modul bei PlatformCore registrieren
             * 
             * Dies registriert das Modul in:
             * - Der Modul-Registry (für Navigation, Sidebar)
             * - Der Datenbank (modules-Tabelle)
             * 
             * Die Config wird automatisch aus config/gathering.php geladen.
             */
            PlatformCore::registerModule([
                'key'        => 'gathering', // Eindeutiger Schlüssel
                'title'      => 'Gathering', // Anzeige-Name
                'group'      => 'dev',
                'routing'    => config('gathering.routing'),
                'guard'      => config('gathering.guard'),
                'navigation' => config('gathering.navigation'),
                'sidebar'    => config('gathering.sidebar'),
            ]);
        }

        /**
         * SCHRITT 2: Routes laden
         * 
         * Routes werden nur geladen, wenn das Modul erfolgreich registriert wurde.
         * 
         * ModuleRouter::group() erstellt automatisch:
         * - Route-Prefix (aus Config)
         * - Middleware (web, auth, etc.)
         * - Domain-Handling (für Subdomain-Modus)
         */
        if (PlatformCore::getModule('gathering')) {
            /**
             * Web-Routes (authentifiziert)
             * 
             * Standard: requireAuth = true
             * Für öffentliche Routes: requireAuth = false
             */
            ModuleRouter::group('gathering', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
            
            /**
             * API-Routes (optional)
             * 
             * Falls dein Modul API-Endpoints hat:
             * 
             * ModuleRouter::apiGroup('gathering', function () {
             *     $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
             * });
             */
        }

        /**
         * SCHRITT 3: Migrationen laden
         * 
         * Lädt alle Migrationen aus database/migrations/
         * Wird automatisch bei `php artisan migrate` ausgeführt.
         */
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        /**
         * SCHRITT 4: Config veröffentlichen
         * 
         * Ermöglicht es, die Config in config/gathering.php zu überschreiben.
         * 
         * Publizieren mit:
         * php artisan vendor:publish --tag=config --provider="Platform\Gathering\GatheringServiceProvider"
         * 
         * WICHTIG: mergeConfigFrom funktioniert auch OHNE Publizierung!
         */
        $this->publishes([
            __DIR__.'/../config/gathering.php' => config_path('gathering.php'),
        ], 'config');

        /**
         * SCHRITT 5: Views laden
         * 
         * Registriert Views unter dem Namespace 'gathering'
         * 
         * Verwendung in Views:
         * @return view('gathering::livewire.dashboard')
         */
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'gathering');
        
        /**
         * SCHRITT 6: Livewire Components registrieren
         * 
         * Registriert alle Livewire-Komponenten automatisch.
         * 
         * Pattern:
         * - Datei: src/Livewire/Dashboard.php
         * - Alias: gathering.dashboard
         * 
         * Verwendung:
         * <livewire:gathering.dashboard />
         */
        $this->registerLivewireComponents();
        
        /**
         * SCHRITT 7: Tools registrieren (optional)
         * 
         * Falls dein Modul AI/Chat-Tools hat:
         * 
         * $this->registerTools();
         */
    }

    /**
     * Registriert alle Livewire-Komponenten automatisch
     * 
     * Scant das src/Livewire/ Verzeichnis rekursiv und registriert
     * alle PHP-Dateien als Livewire-Komponenten.
     * 
     * NAMING CONVENTION:
     * - Datei: src/Livewire/Dashboard.php
     * - Namespace: Platform\Gathering\Livewire\Dashboard
     * - Alias: gathering.dashboard
     * 
     * - Datei: src/Livewire/Entity/Index.php
     * - Namespace: Platform\Gathering\Livewire\Entity\Index
     * - Alias: gathering.entity.index
     * 
     * @return void
     */
    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Gathering\\Livewire';
        $prefix = 'gathering';

        // Prüfe ob Verzeichnis existiert
        if (!is_dir($basePath)) {
            return;
        }

        // Rekursiv alle PHP-Dateien durchsuchen
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            // Nur PHP-Dateien verarbeiten
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            // Relativen Pfad extrahieren
            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            
            // Klassenpfad generieren (z.B. Entity\Index -> Entity\Index)
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            // Prüfe ob Klasse existiert
            if (!class_exists($class)) {
                continue;
            }

            // Alias generieren (z.B. Entity\Index -> entity.index)
            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            // Livewire-Komponente registrieren
            Livewire::component($alias, $class);
        }
    }
}
