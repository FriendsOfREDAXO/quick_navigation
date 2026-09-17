<?php

namespace FriendsOfRedaxo\QuickNavigation\ApiFunction;

use FriendsOfRedaxo\QuickNavigation\Yform\Search;
use rex;
use rex_api_function;
use rex_i18n;

/**
 * Live-search endpoint for the combined YForm quick-navigation button.
 * Searches one table (if table_name is given) or all permitted tables (short list per table),
 * using YForm's own searchable-field conventions and getListValue()-based preview formatting.
 */
class YformSearch extends rex_api_function
{
    protected $published = true;

    public function execute()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!rex::getUser()) {
            echo json_encode(['success' => false, 'error' => 'no permission']);
            exit;
        }

        $tableName = rex_request('table_name', 'string', '');

        if ('filters' === rex_request('mode', 'string', '')) {
            $table = Search::findTableByName($tableName);
            echo json_encode([
                'success' => null !== $table,
                'table_label' => $table ? rex_i18n::translate($table->getName()) : '',
                'filters' => $table ? Search::filterableFields($table) : [],
            ]);
            exit;
        }

        $term = trim(rex_request('term', 'string', ''));
        $filtersRaw = rex_request('filters', 'array', []);

        if ('' === $term && [] === $filtersRaw) {
            echo json_encode(['success' => false, 'error' => 'empty term']);
            exit;
        }

        $filters = [];
        foreach ($filtersRaw as $key => $value) {
            if (is_string($key) && is_scalar($value) && '' !== (string) $value) {
                $filters[$key] = (string) $value;
            }
        }

        $tables = '' !== $tableName
            ? array_filter([Search::findTableByName($tableName)])
            : Search::searchableTables();

        $groups = [];
        $perTableLimit = '' !== $tableName ? 20 : 5;

        foreach ($tables as $table) {
            $results = Search::searchTable($table, $term, $filters, $perTableLimit);
            if ([] === $results) {
                continue;
            }
            $groups[] = [
                'table_name' => $table->getTableName(),
                'table_label' => rex_i18n::translate($table->getName()),
                'results' => $results,
            ];
        }

        echo json_encode([
            'success' => true,
            'groups' => $groups,
        ]);
        exit;
    }

    public function requiresCsrfProtection()
    {
        return false;
    }
}
