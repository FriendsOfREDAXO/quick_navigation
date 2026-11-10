<?php

namespace FriendsOfRedaxo\QuickNavigation\YForm;

use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Security\User;
use Yakamara\YForm\Field\Password;
use Yakamara\YForm\Field\Text;
use Yakamara\YForm\Field\Textarea;
use Yakamara\YForm\Permission\Access;
use Yakamara\YForm\Table\Table;
use Yakamara\YForm\Table\Tables;

use function class_exists;

/** YForm-Tabellen, die ein Benutzer sehen darf – nur, wenn YForm installiert ist */
final class YFormTables
{
    public static function available(): bool
    {
        return class_exists(Tables::class);
    }

    /** @return list<Table> */
    public static function visible(User $user): array
    {
        if (!self::available()) {
            return [];
        }
        $list = [];
        foreach (Tables::all() as $table) {
            if (Access::canView($table, $user)) {
                $list[] = $table;
            }
        }

        return $list;
    }

    public static function canEdit(Table $table, User $user): bool
    {
        return Access::canEdit($table, $user);
    }

    /** Icon-Klassen einer Tabelle */
    public static function icon(Table $table): string
    {
        return 'rex-icon ' . $table->icon();
    }

    /** @param array<string, string|int> $params */
    public static function url(Table $table, array $params = []): string
    {
        return Url::backendPage('yform/data', ['table' => $table->name] + $params);
    }

    /**
     * Spalten der Textfelder (Text, Textarea …) – ohne Passwörter.
     *
     * @return list<string>
     */
    public static function textColumns(Table $table): array
    {
        $columns = [];
        foreach ($table->storedFields() as $field) {
            if ($field instanceof Password || (!$field instanceof Text && !$field instanceof Textarea)) {
                continue;
            }
            foreach ($field->columns() as $column) {
                $columns[] = $column->name;
            }
        }

        return $columns;
    }
}
