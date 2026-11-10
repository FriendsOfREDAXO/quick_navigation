<?php

namespace FriendsOfRedaxo\QuickNavigation\YForm;

use FriendsOfRedaxo\QuickNavigation\Action;
use FriendsOfRedaxo\QuickNavigation\AsProvider;
use FriendsOfRedaxo\QuickNavigation\Item;
use FriendsOfRedaxo\QuickNavigation\Provider;
use FriendsOfRedaxo\QuickNavigation\Query;
use Override;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Data\Query as RecordQuery;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Table\Table;

use function addcslashes;
use function implode;
use function is_scalar;

/**
 * YForm-Datensätze – Textfelder aller Tabellen, die der Benutzer sehen darf, oder die ID. Ohne Eingabe (nur mit „y:“)
 * die neuesten Datensätze je Tabelle.
 */
#[AsProvider('yform', priority: 60, prefix: 'y')]
final class YFormProvider implements Provider
{
    /** Treffer je Tabelle */
    private const int PER_TABLE = 8;

    #[Override]
    public function label(?Query $query = null): string
    {
        return I18n::msg('quick_navigation_group_yform');
    }

    #[Override]
    public function search(Query $query): iterable
    {
        if ($query->isEmpty() && !$query->scoped) {
            return;
        }
        foreach (YFormTables::visible($query->user) as $table) {
            foreach ($this->records($table, $query) as $record) {
                $item = $this->item($table, $record, $query);
                if (null !== $item) {
                    yield $item;
                }
            }
        }
    }

    /** @return iterable<Record> */
    private function records(Table $table, Query $query): iterable
    {
        /** @var RecordQuery $q */
        $q = $table::query()->orderBy('id', 'DESC')->limit($query->isEmpty() ? 3 : self::PER_TABLE);
        if ($query->isEmpty()) {
            return $q->find();
        }
        $columns = YFormTables::textColumns($table);
        if (!$columns && !$query->isId()) {
            return [];
        }
        // jedes Suchwort muss in einer der Textspalten vorkommen
        foreach ($query->words as $word) {
            $like = '%' . addcslashes($word, '%_\\') . '%';
            $id = $query->isId() ? (int) $word : null;
            $q->whereAny(static function (RecordQuery $any) use ($columns, $like, $id): void {
                foreach ($columns as $column) {
                    $any->whereLike($column, $like);
                }
                if (null !== $id) {
                    $any->where('id', $id);
                }
            });
        }

        return $q->find();
    }

    private function item(Table $table, Record $record, Query $query): ?Item
    {
        $title = $table->recordLabel($record);
        // für die Relevanz alle Texte des Datensatzes
        $texts = [];
        foreach (YFormTables::textColumns($table) as $column) {
            $value = $record->get($column);
            if (is_scalar($value)) {
                $texts[] = (string) $value;
            }
        }
        $score = $query->isId() ? 1000 : $query->score($title, implode(' ', $texts));
        if (0 === $score) {
            return null;
        }
        $canEdit = YFormTables::canEdit($table, $query->user);

        return new Item(
            title: $title,
            url: YFormTables::url($table, ['func' => 'edit', 'id' => (int) $record->id]),
            meta: $table->label() . ' · ID ' . $record->id,
            icon: YFormTables::icon($table),
            badge: $canEdit ? null : I18n::msg('quick_navigation_yform_readonly'),
            actions: [new Action(I18n::msg('quick_navigation_yform_table'), YFormTables::url($table), 'rex-icon rex-icon-table')],
            score: $score,
        );
    }
}
