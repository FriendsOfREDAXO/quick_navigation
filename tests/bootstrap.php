<?php

/*
 * Unit-Tests laufen ohne REDAXO (Autoloader des AddOns oder des Projekts genügt).
 *
 * Die Integrationstests brauchen eine gestartete REDAXO-Installation mit Datenbank – Pfad zu deren Bootstrap in
 * REDAXO_BOOTSTRAP, im Entwicklungs-Repository des Cores zum Beispiel:
 *
 *     REDAXO_BOOTSTRAP=/pfad/zu/redaxo/.tools/bootstrap.php vendor/bin/phpunit -c /pfad/zu/quick_navigation/phpunit.dist.xml
 *
 * Ohne REDAXO_BOOTSTRAP werden sie übersprungen.
 */

$bootstrap = getenv('REDAXO_BOOTSTRAP');
if (is_string($bootstrap) && '' !== $bootstrap) {
    require $bootstrap;
    define('QUICK_NAVIGATION_INTEGRATION', true);
} else {
    require dirname(__DIR__) . '/vendor/autoload.php';
    define('QUICK_NAVIGATION_INTEGRATION', false);
}

// Testklassen des AddOns (autoload-dev greift nicht, wenn ein anderes Projekt den Autoloader stellt)
spl_autoload_register(static function (string $class): void {
    $prefix = 'FriendsOfRedaxo\\QuickNavigation\\Tests\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});
