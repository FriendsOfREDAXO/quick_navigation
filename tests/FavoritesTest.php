<?php

namespace FriendsOfRedaxo\QuickNavigation\Tests;

use FriendsOfRedaxo\QuickNavigation\Favorites;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** @internal */
final class FavoritesTest extends TestCase
{
    #[DataProvider('provideNormalize')]
    public function testNormalize(string $url, ?string $expected): void
    {
        self::assertSame($expected, Favorites::normalize($url));
    }

    /** @return iterable<string, array{string, ?string}> */
    public static function provideNormalize(): iterable
    {
        yield 'relative Backend-Adresse' => ['index.php?page=structure&category_id=5', 'index.php?page=structure&category_id=5'];
        yield 'ohne Pfad' => ['?page=structure', 'index.php?page=structure'];
        yield 'absolute Backend-Adresse' => ['https://example.com/redaxo/index.php?page=mediapool/media', 'index.php?page=mediapool%2Fmedia'];
        yield 'Token entfernt' => ['index.php?page=system/settings&func=generate&_csrf_token=abc', 'index.php?page=system%2Fsettings&func=generate'];
        yield 'Meldungen entfernt' => ['index.php?page=yform/data&table=t&func=edit&id=2&msg=saved', 'index.php?page=yform%2Fdata&table=t&func=edit&id=2'];
        yield 'API-Aufruf entfernt' => ['index.php?page=structure&rex-api-call=foo', 'index.php?page=structure'];
        yield 'Arrays bleiben' => ['index.php?page=yform/data&table=t&filter[name]=x', 'index.php?page=yform%2Fdata&table=t&filter%5Bname%5D=x'];
        yield 'ohne page' => ['index.php?article_id=5', null];
        yield 'leere page' => ['index.php?page=', null];
        yield 'fremde Seite' => ['https://example.com/?page=structure', null];
        yield 'relativer Pfad auf index.php' => ['/redaxo/index.php?page=x', 'index.php?page=x'];
        yield 'anderes Skript' => ['backend.php?page=structure', null];
        yield 'javascript' => ['javascript:alert(1)', null];
        yield 'Befehl im Skript' => ['#qn-favorite-page', null];
        yield 'leer' => ['', null];
    }
}
