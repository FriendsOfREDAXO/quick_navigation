<?php

namespace FriendsOfRedaxo\QuickNavigation\Tests\Integration;

use FriendsOfRedaxo\QuickNavigation\Item;
use FriendsOfRedaxo\QuickNavigation\Provider;
use FriendsOfRedaxo\QuickNavigation\Query;
use FriendsOfRedaxo\QuickNavigation\QuickNavigationAddon;
use Override;
use PHPUnit\Framework\TestCase;
use Redaxo\Core\Content\ArticleHandler;
use Redaxo\Core\Content\CategoryHandler;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Language\Language;
use Redaxo\Core\MediaPool\Media;
use Redaxo\Core\MediaPool\MediaCategoryHandler;
use Redaxo\Core\MediaPool\MediaPoolCache;
use Redaxo\Core\Security\User;

use function array_map;
use function is_array;
use function iterator_to_array;

/**
 * Testdaten für die Rechteprüfung: zwei Kategorien (A, B) mit je einem Artikel, zwei Medienkategorien (A, B) mit je
 * einem Medium, ein Redakteur mit Rechten nur auf A (Struktur-Mountpoint, Medienkategorie, Sprache, Artikel/Kategorien
 * anlegen) und ein Admin. Alles mit „qn-test“ im Namen, nach den Tests wieder entfernt.
 *
 * @internal
 */
abstract class IntegrationTestCase extends TestCase
{
    protected static int $categoryA;
    protected static int $categoryB;
    protected static int $articleA;
    protected static int $articleB;
    protected static int $mediaCategoryA;
    protected static int $mediaCategoryB;
    protected static int $roleId;
    protected static int $editorId;
    protected static int $adminId;

    /** @var list<int> */
    private static array $mediaIds = [];

    #[Override]
    public static function setUpBeforeClass(): void
    {
        if (!QUICK_NAVIGATION_INTEGRATION) {
            return;
        }
        self::$categoryA = CategoryHandler::add(['parent_id' => null, 'name' => 'qn-test Alpha Kategorie'])->id;
        self::$categoryB = CategoryHandler::add(['parent_id' => null, 'name' => 'qn-test Beta Kategorie'])->id;
        self::$articleA = ArticleHandler::add(['category_id' => self::$categoryA, 'name' => 'qn-test Alpha Artikel'])->id;
        self::$articleB = ArticleHandler::add(['category_id' => self::$categoryB, 'name' => 'qn-test Beta Artikel'])->id;

        self::$mediaCategoryA = MediaCategoryHandler::add(['parent_id' => null, 'name' => 'qn-test Medien A'])->id;
        self::$mediaCategoryB = MediaCategoryHandler::add(['parent_id' => null, 'name' => 'qn-test Medien B'])->id;
        self::$mediaIds = [self::media('qn-test-a.txt', self::$mediaCategoryA), self::media('qn-test-b.txt', self::$mediaCategoryB)];

        $role = Sql::factory()->setTable('rex_user_role');
        $role->setValue('name', 'qn-test Rolle');
        $role->setArrayValue('perms', [
            'general' => '',
            'options' => '|addArticle[]|addCategory[]|',
            'extras' => '',
            'structure' => '|' . self::$categoryA . '|',
            'media' => '|' . self::$mediaCategoryA . '|',
            'language' => '|' . Language::getStartId() . '|',
        ]);
        $role->addGlobalCreateFields('qn-test');
        $role->addGlobalUpdateFields('qn-test');
        $role->insert();
        self::$roleId = (int) $role->getLastId();

        self::$editorId = self::user('qn-test-redakteur', false);
        self::$adminId = self::user('qn-test-admin', true);
        Sql::factory()->setTable('rex_user_role_assignment')
            ->setValue('user_id', self::$editorId)
            ->setValue('role_id', self::$roleId)
            ->insert();
    }

    #[Override]
    public static function tearDownAfterClass(): void
    {
        if (!QUICK_NAVIGATION_INTEGRATION) {
            return;
        }
        ArticleHandler::delete(self::$articleA);
        ArticleHandler::delete(self::$articleB);
        CategoryHandler::delete(self::$categoryA);
        CategoryHandler::delete(self::$categoryB);
        foreach (self::$mediaIds as $id) {
            Sql::factory()->setQuery('DELETE FROM rex_media_translation WHERE media_id = ?', [$id]);
            Sql::factory()->setQuery('DELETE FROM rex_media WHERE id = ?', [$id]);
        }
        self::forgetMedia('qn-test-a.txt');
        self::forgetMedia('qn-test-b.txt');
        MediaCategoryHandler::delete(self::$mediaCategoryA);
        MediaCategoryHandler::delete(self::$mediaCategoryB);
        foreach ([self::$editorId, self::$adminId] as $id) {
            Sql::factory()->setQuery('DELETE FROM rex_user_role_assignment WHERE user_id = ?', [$id]);
            Sql::factory()->setQuery('DELETE FROM rex_user WHERE id = ?', [$id]);
            QuickNavigationAddon::instance()->removeConfig('favorites.' . $id);
            User::clearInstance($id);
        }
        Sql::factory()->setQuery('DELETE FROM rex_user_role WHERE id = ?', [self::$roleId]);
    }

    #[Override]
    protected function setUp(): void
    {
        if (!QUICK_NAVIGATION_INTEGRATION) {
            self::markTestSkipped('Integrationstest: REDAXO_BOOTSTRAP ist nicht gesetzt.');
        }
    }

    protected static function editor(): User
    {
        return User::require(self::$editorId);
    }

    protected static function admin(): User
    {
        return User::require(self::$adminId);
    }

    /**
     * Titel der Treffer einer Quelle.
     *
     * @return list<string>
     */
    protected static function titles(Provider $provider, string $term, User $user, bool $scoped = true): array
    {
        $items = $provider->search(new Query($term, $user, 30, $scoped));

        return array_map(static fn (Item $item) => $item->title, [...(is_array($items) ? $items : iterator_to_array($items, false))]);
    }

    private static function media(string $filename, int $categoryId): int
    {
        // der Cache des Medienpools hängt am Dateinamen – Reste eines früheren Laufs entfernen
        self::forgetMedia($filename);
        $sql = Sql::factory()->setTable('rex_media');
        $sql->setValue('filename', $filename);
        $sql->setValue('originalname', $filename);
        $sql->setValue('category_id', $categoryId);
        $sql->setValue('filetype', 'text/plain');
        $sql->setValue('filesize', 1);
        $sql->addGlobalCreateFields('qn-test');
        $sql->addGlobalUpdateFields('qn-test');
        $sql->insert();
        $id = (int) $sql->getLastId();
        foreach (Language::getAllIds() as $languageId) {
            Sql::factory()->setTable('rex_media_translation')
                ->setValue('media_id', $id)
                ->setValue('language_id', $languageId)
                ->setValue('title', $filename)
                ->insert();
        }

        return $id;
    }

    private static function forgetMedia(string $filename): void
    {
        MediaPoolCache::delete($filename);
        Media::clearInstances($filename);
    }

    private static function user(string $login, bool $admin): int
    {
        $sql = Sql::factory()->setTable('rex_user');
        $sql->setValue('login', $login);
        $sql->setValue('name', $login);
        $sql->setValue('status', 1);
        $sql->setValue('admin', $admin ? 1 : 0);
        $sql->addGlobalCreateFields('qn-test');
        $sql->addGlobalUpdateFields('qn-test');
        $sql->insert();

        return (int) $sql->getLastId();
    }
}
