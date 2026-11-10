<?php

namespace FriendsOfRedaxo\QuickNavigation;

use Redaxo\Core\Security\User;

use function array_filter;
use function array_slice;
use function array_values;
use function count;
use function http_build_query;
use function is_array;
use function is_string;
use function mb_substr;
use function parse_str;
use function parse_url;
use function str_ends_with;
use function trim;

/**
 * Favoriten je Benutzer (Konfiguration des AddOns, Schlüssel „favorites.<id>“) – Backend-Adressen mit Titel, zweiter
 * Zeile und Icon. Gespeichert wird die Adresse ohne Sicherheitstoken und Meldungen.
 */
final class Favorites
{
    public const int MAX = 60;

    /** Parameter, die nicht zur Adresse gehören (Token, Meldungen nach dem Speichern) */
    private const array DROP = ['_csrf_token', 'rex-api-call', 'msg', 'info', 'warning', 'saved', 'qn_media_sort'];

    /** @return list<array{url: string, title: string, meta: ?string, icon: ?string}> */
    public static function all(User $user): array
    {
        $stored = QuickNavigationAddon::instance()->getConfig(self::key($user), []);
        $list = [];
        foreach (is_array($stored) ? $stored : [] as $f) {
            if (is_array($f) && is_string($f['url'] ?? null) && is_string($f['title'] ?? null)) {
                $list[] = [
                    'url' => $f['url'],
                    'title' => $f['title'],
                    'meta' => is_string($f['meta'] ?? null) ? $f['meta'] : null,
                    'icon' => is_string($f['icon'] ?? null) ? $f['icon'] : null,
                ];
            }
        }

        return $list;
    }

    /** @return array<string, true> normalisierte Adressen */
    public static function urls(User $user): array
    {
        $urls = [];
        foreach (self::all($user) as $favorite) {
            $urls[$favorite['url']] = true;
        }

        return $urls;
    }

    /**
     * Favorit hinzufügen bzw. entfernen.
     *
     * @return bool ob die Adresse jetzt ein Favorit ist
     */
    public static function toggle(User $user, string $url, string $title, ?string $meta, ?string $icon): bool
    {
        $url = self::normalize($url);
        if (null === $url) {
            return false;
        }
        $list = self::all($user);
        $kept = array_values(array_filter($list, static fn (array $f) => $f['url'] !== $url));
        $added = count($kept) === count($list);
        if ($added) {
            $kept[] = [
                'url' => $url,
                'title' => mb_substr(trim($title) ?: $url, 0, 200),
                'meta' => null !== $meta && '' !== trim($meta) ? mb_substr(trim($meta), 0, 200) : null,
                'icon' => null !== $icon && '' !== trim($icon) ? mb_substr(trim($icon), 0, 100) : null,
            ];
        }
        QuickNavigationAddon::instance()->setConfig(self::key($user), array_slice($kept, -self::MAX));

        return $added;
    }

    /**
     * Adresse einer Backend-Seite in fester Form („index.php?page=…“), ohne Token; null für alles andere (Website,
     * fremde Adressen, Skripte).
     */
    public static function normalize(string $url): ?string
    {
        $parts = parse_url($url);
        if (!is_array($parts) || isset($parts['scheme']) || isset($parts['host'])) {
            // absolute Adressen nur, wenn sie aufs Backend zeigen – die Palette liefert relative
            if (!is_array($parts) || !isset($parts['path']) || !str_ends_with($parts['path'], '/redaxo/index.php')) {
                return null;
            }
        }
        $path = $parts['path'] ?? '';
        if ('' !== $path && 'index.php' !== $path && !str_ends_with($path, '/index.php')) {
            return null;
        }
        parse_str($parts['query'] ?? '', $query);
        if (!isset($query['page']) || !is_string($query['page']) || '' === $query['page']) {
            return null;
        }
        foreach (self::DROP as $param) {
            unset($query[$param]);
        }

        return 'index.php?' . http_build_query($query);
    }

    private static function key(User $user): string
    {
        return 'favorites.' . $user->id;
    }
}
