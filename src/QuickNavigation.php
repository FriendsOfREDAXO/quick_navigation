<?php

namespace FriendsOfRedaxo\QuickNavigation;

use Redaxo\Core\ClassDiscovery;
use Redaxo\Core\Core;
use Redaxo\Core\Log\Logger;
use Redaxo\Core\Security\User;
use Throwable;

use function array_map;
use function array_slice;
use function is_string;
use function preg_match;
use function strtolower;
use function trim;
use function uasort;
use function usort;

/**
 * Befehlspalette: sammelt die Quellen ({@see Provider}) und fragt sie ab.
 *
 * Quellen anderer AddOns werden über das Attribut {@see AsProvider} gefunden. Zur Laufzeit (z. B. abhängig von der
 * Konfiguration) geht es auch ohne Attribut:
 *
 *     QuickNavigation::register('news', new NewsProvider(), priority: 40, prefix: 'n');
 *     QuickNavigation::unregister('users');
 */
final class QuickNavigation
{
    /** Treffer je Gruppe – und wenn nur in einer Quelle gesucht wird (Kürzel) */
    public const int LIMIT = 6;
    public const int LIMIT_SCOPED = 30;

    /** @var array<string, array{provider: Provider|class-string<Provider>, priority: int, prefix: ?string}> */
    private static array $registered = [];

    /** @var list<string> */
    private static array $removed = [];

    public static function register(string $key, Provider $provider, int $priority = 0, ?string $prefix = null): void
    {
        self::$registered[$key] = ['provider' => $provider, 'priority' => $priority, 'prefix' => $prefix];
    }

    public static function unregister(string $key): void
    {
        unset(self::$registered[$key]);
        self::$removed[] = $key;
    }

    /**
     * Alle Quellen, höchste Priorität zuerst.
     *
     * @return array<string, array{provider: Provider|class-string<Provider>, priority: int, prefix: ?string}>
     */
    public static function providers(): array
    {
        $all = [];
        foreach (ClassDiscovery::getInstance()->discoverByAttribute(AsProvider::class, Provider::class) as $class => $attr) {
            if (!isset($all[$attr->key]) || $all[$attr->key]['priority'] < $attr->priority) {
                $all[$attr->key] = ['provider' => $class, 'priority' => $attr->priority, 'prefix' => $attr->prefix];
            }
        }
        $all = self::$registered + $all;
        foreach (self::$removed as $key) {
            unset($all[$key]);
        }
        uasort($all, static fn (array $a, array $b) => $b['priority'] <=> $a['priority']);

        return $all;
    }

    /**
     * Kürzel der Quellen für die Hinweise in der Palette.
     *
     * @return list<array{key: string, prefix: string, label: string}>
     */
    public static function prefixes(): array
    {
        $list = [];
        foreach (self::providers() as $key => $entry) {
            if (null !== $entry['prefix']) {
                $list[] = ['key' => $key, 'prefix' => $entry['prefix'], 'label' => self::instance($entry['provider'])->label()];
            }
        }

        return $list;
    }

    /**
     * Ergebnis für die Eingabe, gruppiert nach Quelle.
     *
     * @return array{scope: ?string, groups: list<array{key: string, label: string, items: list<array<string, mixed>>}>}
     */
    public static function search(string $input, User $user): array
    {
        $providers = self::providers();
        $input = trim($input);

        // „m: logo“ → nur in der Quelle mit dem Kürzel m; „> cache“ → nur Befehle
        $scope = null;
        if (1 === preg_match('/^(>)\s*(.*)$/su', $input, $m) || 1 === preg_match('/^([a-z]+):\s*(.*)$/iu', $input, $m)) {
            foreach ($providers as $key => $entry) {
                if (null !== $entry['prefix'] && strtolower($m[1]) === strtolower($entry['prefix'])) {
                    $scope = $key;
                    $input = $m[2];
                    break;
                }
            }
        }

        $groups = [];
        foreach ($providers as $key => $entry) {
            if (null !== $scope && $scope !== $key) {
                continue;
            }
            $query = new Query($input, $user, null !== $scope ? self::LIMIT_SCOPED : self::LIMIT, null !== $scope);
            try {
                $provider = self::instance($entry['provider']);
                $items = [];
                foreach ($provider->search($query) as $item) {
                    $items[] = $item;
                }
            } catch (Throwable $e) {
                // eine fehlerhafte Quelle soll die Suche nicht lahmlegen
                if (Core::isDevMode()) {
                    throw $e;
                }
                Logger::logException($e);
                continue;
            }
            if (!$items) {
                continue;
            }
            if (!$query->isEmpty()) {
                usort($items, static fn (Item $a, Item $b) => $b->score <=> $a->score);
            }
            $items = array_slice($items, 0, $query->limit);
            $groups[] = [
                'key' => $key,
                'label' => $provider->label($query),
                'items' => array_map(static fn (Item $i) => $i->toArray(), $items),
            ];
        }

        return ['scope' => $scope, 'groups' => $groups];
    }

    /** @param Provider|class-string<Provider> $provider */
    private static function instance(Provider|string $provider): Provider
    {
        static $instances = [];
        if (!is_string($provider)) {
            return $provider;
        }

        return $instances[$provider] ??= new $provider();
    }
}
