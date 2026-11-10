<?php

namespace FriendsOfRedaxo\QuickNavigation\Domain;

use FriendsOfRedaxo\QuickNavigation\ExtensionPoint\QuickNavigationDomains;
use Redaxo\Core\ExtensionPoint\Extension;

use function array_search;
use function count;
use function in_array;

/** Domains, die AddOns per {@see QuickNavigationDomains} eintragen */
final class Domains
{
    /** @var array<string, Domain>|null */
    private static ?array $all = null;

    /** @return array<string, Domain> */
    public static function all(): array
    {
        if (null !== self::$all) {
            return self::$all;
        }
        self::$all = [];
        foreach (Extension::dispatch(new QuickNavigationDomains()) as $domain) {
            if ($domain instanceof Domain) {
                self::$all[$domain->key] ??= $domain;
            }
        }

        return self::$all;
    }

    /** Erst ab zwei Domains lohnt die Unterscheidung */
    public static function multiple(): bool
    {
        return count(self::all()) > 1;
    }

    public static function get(string $key): ?Domain
    {
        return self::all()[$key] ?? null;
    }

    /**
     * Domain einer Seite: die mit der tiefsten Einstiegskategorie im Pfad (bzw. die der ganzen Struktur).
     *
     * @param list<int> $path Kategorien über der Seite, mit der Seite selbst
     */
    public static function forPath(array $path): ?Domain
    {
        $found = null;
        $depth = -1;
        foreach (self::all() as $domain) {
            if (0 === $domain->mountId) {
                if ($depth < 0) {
                    $found = $domain;
                    $depth = 0;
                }
                continue;
            }
            $i = array_search($domain->mountId, $path, true);
            if (false !== $i && $i + 1 > $depth) {
                $found = $domain;
                $depth = $i + 1;
            }
        }

        return $found;
    }

    /**
     * Einstiegskategorien der anderen Domains – im Baum einer Domain ab Hauptebene ausgeblendet.
     *
     * @return list<int>
     */
    public static function otherMounts(Domain $domain): array
    {
        $list = [];
        foreach (self::all() as $other) {
            if ($other->key !== $domain->key && 0 !== $other->mountId && !in_array($other->mountId, $list, true)) {
                $list[] = $other->mountId;
            }
        }

        return $list;
    }
}
