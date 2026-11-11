<?php

namespace FriendsOfRedaxo\QuickNavigation\Tests\Integration;

use FriendsOfRedaxo\QuickNavigation\Command\CommandProvider;
use FriendsOfRedaxo\QuickNavigation\Favorites;
use FriendsOfRedaxo\QuickNavigation\Media\MediaFinder;
use FriendsOfRedaxo\QuickNavigation\Provider\MediaProvider;
use FriendsOfRedaxo\QuickNavigation\Provider\PageProvider;
use FriendsOfRedaxo\QuickNavigation\Provider\StructureProvider;
use FriendsOfRedaxo\QuickNavigation\Provider\UserProvider;
use FriendsOfRedaxo\QuickNavigation\StructureTree;
use Redaxo\Core\Backend\Controller;
use Redaxo\Core\Backend\MainPage;
use Redaxo\Core\Core;
use Redaxo\Core\Language\Language;
use Redaxo\Core\Security\CsrfToken;
use Redaxo\Core\Translation\I18n;
use Symfony\Component\HttpFoundation\Request;

use function array_column;
use function array_map;

/**
 * Jede Quelle liefert nur, was der Benutzer öffnen darf – der Redakteur hat Rechte nur auf Kategorie A, Medienkategorie A
 * und keine Benutzerverwaltung, der Admin auf alles.
 *
 * @internal
 */
final class PermissionTest extends IntegrationTestCase
{
    public function testStructureOnlyWithinMountpoint(): void
    {
        $provider = new StructureProvider();

        $editor = self::titles($provider, 'qn-test', self::editor());
        self::assertContains('qn-test Alpha Artikel', $editor);
        self::assertContains('qn-test Alpha Kategorie', $editor);
        self::assertNotContains('qn-test Beta Artikel', $editor);
        self::assertNotContains('qn-test Beta Kategorie', $editor);

        $admin = self::titles($provider, 'qn-test', self::admin());
        self::assertContains('qn-test Beta Artikel', $admin);
        self::assertContains('qn-test Beta Kategorie', $admin);
    }

    public function testStructureById(): void
    {
        $provider = new StructureProvider();

        self::assertSame([], self::titles($provider, (string) self::$articleB, self::editor()));
        self::assertSame(['qn-test Beta Artikel'], self::titles($provider, (string) self::$articleB, self::admin()));
    }

    public function testTreeStartsAtMountpoints(): void
    {
        $tree = new StructureTree(self::editor(), Language::getStartId());
        self::assertSame([self::$categoryA], array_column($tree->level(0), 'id'));
        self::assertSame([], $tree->level(self::$categoryB));

        $found = $tree->search('qn-test');
        self::assertSame(2, $found['count']);
        self::assertSame([self::$categoryA], array_column($found['items'], 'id'));

        $admin = new StructureTree(self::admin(), Language::getStartId());
        self::assertContains(self::$categoryB, array_column($admin->level(0), 'id'));
    }

    public function testMediaOnlyFromPermittedCategories(): void
    {
        $files = static fn (array $list) => array_map(static fn ($media) => $media->fileName, $list);

        self::assertSame(['qn-test-a.txt'], $files(MediaFinder::find(self::editor(), 'qn-test-')));
        self::assertEqualsCanonicalizing(['qn-test-a.txt', 'qn-test-b.txt'], $files(MediaFinder::find(self::admin(), 'qn-test-')));

        $provider = new MediaProvider();
        $editor = self::titles($provider, 'qn-test', self::editor());
        self::assertContains('qn-test Medien A', $editor);
        self::assertContains('qn-test-a.txt', $editor);
        self::assertNotContains('qn-test Medien B', $editor);
        self::assertNotContains('qn-test-b.txt', $editor);
    }

    public function testPagesFollowPagePermissions(): void
    {
        $pages = Controller::getPages();
        try {
            Controller::setPages([
                'structure' => new MainPage('system', 'structure', 'qn-test Struktur'),
                'users' => new MainPage('system', 'users', 'qn-test Benutzer')->setRequiredPermissions('users[]'),
            ]);
            $provider = new PageProvider();

            self::assertSame(['qn-test Struktur'], self::titles($provider, 'qn-test', self::editor()));
            self::assertEqualsCanonicalizing(['qn-test Struktur', 'qn-test Benutzer'], self::titles($provider, 'qn-test', self::admin()));
        } finally {
            Controller::setPages($pages);
        }
    }

    public function testUsersOnlyWithUserManagement(): void
    {
        $provider = new UserProvider();

        self::assertSame([], self::titles($provider, 'qn-test', self::editor()));
        self::assertContains('qn-test-redakteur', self::titles($provider, 'qn-test', self::admin()));
    }

    public function testCommandsFollowPermissions(): void
    {
        // „Cache löschen“ trägt ein CSRF-Token – das braucht einen Request (wie in den Tests des Cores)
        $previousRequest = Core::hasRequest() ? Core::getRequest() : null;
        Core::setRequest(Request::create('/'));
        try {
            $this->assertCommands();
        } finally {
            CsrfToken::removeAll();
            Core::setRequest($previousRequest);
        }
    }

    private function assertCommands(): void
    {
        $provider = new CommandProvider();
        $cacheTitle = I18n::msg('quick_navigation_cmd_cache');
        $userTitle = I18n::msg('quick_navigation_cmd_user');

        // ohne Kategorie: Hauptebene – darauf hat der Redakteur keine Rechte
        $editor = self::titles($provider, '', self::editor());
        self::assertNotContains($cacheTitle, $editor);
        self::assertNotContains($userTitle, $editor);
        self::assertSame([], array_filter($editor, static fn (string $t) => str_contains($t, I18n::msg('quick_navigation_root'))));

        // in Kategorie A darf er Artikel und Kategorien anlegen, in B nicht
        $_GET['qn_category'] = (string) self::$categoryA;
        try {
            self::assertCount(2, array_filter(self::titles($provider, '', self::editor()), static fn (string $t) => str_contains($t, 'qn-test Alpha Kategorie')));
            $_GET['qn_category'] = (string) self::$categoryB;
            self::assertSame([], array_filter(self::titles($provider, '', self::editor()), static fn (string $t) => str_contains($t, 'qn-test Beta Kategorie')));
        } finally {
            unset($_GET['qn_category']);
        }

        $admin = self::titles($provider, '', self::admin());
        self::assertContains($cacheTitle, $admin);
        self::assertContains($userTitle, $admin);
    }

    public function testFavoritesPerUser(): void
    {
        self::assertTrue(Favorites::toggle(self::editor(), 'index.php?page=structure&category_id=1&_csrf_token=x', 'qn-test Favorit', null, null));
        self::assertSame(['index.php?page=structure&category_id=1'], array_column(Favorites::all(self::editor()), 'url'));
        self::assertSame([], Favorites::all(self::admin()));

        // erneut umschalten entfernt
        self::assertFalse(Favorites::toggle(self::editor(), 'index.php?page=structure&category_id=1', 'qn-test Favorit', null, null));
        self::assertSame([], Favorites::all(self::editor()));

        // keine fremden Adressen
        self::assertFalse(Favorites::toggle(self::editor(), 'https://example.com/?page=structure', 'qn-test', null, null));
        self::assertSame([], Favorites::all(self::editor()));
    }
}
