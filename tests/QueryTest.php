<?php

namespace FriendsOfRedaxo\QuickNavigation\Tests;

use FriendsOfRedaxo\QuickNavigation\Query;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Redaxo\Core\Security\User;
use ReflectionClass;

/** @internal */
final class QueryTest extends TestCase
{
    private static function query(string $term, bool $scoped = false): Query
    {
        // score() und Co. brauchen den Benutzer nicht – ein leeres Objekt genügt
        $user = new ReflectionClass(User::class)->newInstanceWithoutConstructor();

        return new Query($term, $user, 6, $scoped);
    }

    public function testWords(): void
    {
        self::assertSame(['neue', 'öffnungszeiten'], self::query('  Neue   Öffnungszeiten ')->words);
        self::assertTrue(self::query('   ')->isEmpty());
        self::assertFalse(self::query('a')->isEmpty());
    }

    #[DataProvider('provideIsId')]
    public function testIsId(string $term, bool $expected): void
    {
        self::assertSame($expected, self::query($term)->isId());
    }

    /** @return iterable<string, array{string, bool}> */
    public static function provideIsId(): iterable
    {
        yield 'Zahl' => ['42', true];
        yield 'Zahl mit Leerraum' => [' 42 ', true];
        yield 'Text' => ['42a', false];
        yield 'zwei Zahlen' => ['4 2', false];
        yield 'leer' => ['', false];
    }

    public function testLikeEscapesWildcardsAndUsesLongestWord(): void
    {
        self::assertSame('%100\%\_sicher%', self::query('100%_sicher')->like());
        self::assertSame('%projekte%', self::query('neue projekte')->like());
    }

    public function testEmptyQueryMatchesEverything(): void
    {
        self::assertGreaterThan(0, self::query('')->score('irgendwas'));
    }

    public function testEveryWordMustMatch(): void
    {
        $query = self::query('neue zeiten');
        self::assertGreaterThan(0, $query->score('Neue Öffnungszeiten'));
        self::assertSame(0, $query->score('Neue Projekte'));
        // auch in weiteren Texten (z. B. dem Pfad) zählt ein Wort als gefunden
        self::assertGreaterThan(0, $query->score('Neue Projekte', 'Ältere Zeiten'));
    }

    public function testRanking(): void
    {
        $query = self::query('pro');
        $exact = self::query('projekte')->score('Projekte');
        $startsWith = $query->score('Projekte');
        $wordStart = $query->score('Neue Projekte');
        $contains = $query->score('Reproduktion');
        $onlyElsewhere = $query->score('Neuigkeiten', 'Projekte');

        self::assertGreaterThan($startsWith, $exact);
        self::assertGreaterThan($wordStart, $startsWith);
        self::assertGreaterThan($contains, $wordStart);
        self::assertGreaterThan($onlyElsewhere, $contains);
        self::assertGreaterThan(0, $onlyElsewhere);
    }

    public function testShorterTitlesFirstOnSameQuality(): void
    {
        $query = self::query('pro');
        self::assertGreaterThan($query->score('Projekte und mehr'), $query->score('Projekte'));
    }

    public function testCaseInsensitive(): void
    {
        self::assertSame(self::query('PROJEKTE')->score('projekte'), self::query('projekte')->score('PROJEKTE'));
    }
}
