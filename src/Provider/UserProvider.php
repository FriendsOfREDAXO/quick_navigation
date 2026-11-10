<?php

namespace FriendsOfRedaxo\QuickNavigation\Provider;

use FriendsOfRedaxo\QuickNavigation\AsProvider;
use FriendsOfRedaxo\QuickNavigation\Item;
use FriendsOfRedaxo\QuickNavigation\Provider;
use FriendsOfRedaxo\QuickNavigation\Query;
use Override;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Translation\I18n;

/** Benutzer – nur mit dem Recht auf die Benutzerverwaltung; Administratoren nur für Administratoren */
#[AsProvider('users', priority: 50, prefix: 'u')]
final class UserProvider implements Provider
{
    #[Override]
    public function label(?Query $query = null): string
    {
        return I18n::msg('quick_navigation_group_users');
    }

    #[Override]
    public function search(Query $query): iterable
    {
        $user = $query->user;
        if (!$user->hasPerm('users[]') || ($query->isEmpty() && !$query->scoped)) {
            return;
        }
        $where = $user->admin ? '' : ' AND admin = 0';
        $rows = $query->isEmpty()
            ? Sql::factory()->getArray('SELECT id, name, login, email, status, admin FROM rex_user WHERE 1' . $where . ' ORDER BY lastlogin DESC LIMIT 30')
            : Sql::factory()->getArray(
                'SELECT id, name, login, email, status, admin FROM rex_user WHERE (name LIKE ? OR login LIKE ? OR email LIKE ?)' . $where . ' LIMIT 60',
                [$query->like(), $query->like(), $query->like()],
            );

        foreach ($rows as $row) {
            $name = (string) ($row['name'] ?: $row['login']);
            $score = $query->score($name, (string) $row['login'], (string) $row['email']);
            if (0 === $score) {
                continue;
            }
            yield new Item(
                title: $name,
                url: Url::backendPage('users/users', ['user_id' => (int) $row['id']]),
                meta: $row['login'] . ($row['email'] ? ' · ' . $row['email'] : ''),
                icon: $row['status'] ? 'rex-icon rex-icon-user' : 'rex-icon rex-icon-user-inactive',
                badge: $row['admin'] ? I18n::msg('quick_navigation_admin') : null,
                score: $score,
            );
        }
    }
}
