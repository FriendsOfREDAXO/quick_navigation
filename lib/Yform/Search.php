<?php

namespace FriendsOfRedaxo\QuickNavigation\Yform;

use rex;
use rex_addon;
use rex_be_controller;
use rex_csrf_token;
use rex_yform_manager_table;
use rex_yform_manager_dataset;
use rex_yform_manager;
use rex_sql;
use rex_clang;
use rex_url;
use rex_i18n;

/**
 * Core search logic for the combined YForm quick-navigation button:
 * table discovery (permission-respecting, hidden-tables included), per-field-type
 * searchable-field resolution (including optional yform_lang_fields JSON columns),
 * and a small dataset search across one or all tables.
 */
class Search
{
    /** Field types from the optional yform_lang_fields addon: JSON, no native `search` flag. */
    private const LANG_JSON_FIELD_TYPES = ['lang_text', 'lang_textarea'];

    /**
     * @return list<rex_yform_manager_table>
     */
    public static function searchableTables(): array
    {
        $user = rex::getUser();
        if (!$user) {
            return [];
        }

        $yform = rex_addon::get('yform');
        $yperm_suffix = version_compare($yform->getVersion(), '4.0.0-dev', '>=') ? '_edit' : '';

        $tables = [];
        foreach (rex_yform_manager_table::getAll() as $table) {
            if (!$table->isActive()) {
                continue;
            }
            // NOTE: isHidden() only means "no menu entry in YForm manager" — it must NOT
            // gate search/access. Access control is solely the permission check below.
            if (!$user->isAdmin() && !$user->getComplexPerm('yform_manager_table' . $yperm_suffix)->hasPerm($table->getTableName())) {
                continue;
            }
            $tables[] = $table;
        }

        return $tables;
    }

    public static function findTableByName(string $tableName): ?rex_yform_manager_table
    {
        foreach (self::searchableTables() as $table) {
            if ($table->getTableName() === $tableName) {
                return $table;
            }
        }
        return null;
    }

    /**
     * Detects whether the currently open backend page already displays the given table,
     * so edit links can reuse that addon page instead of always falling back to the
     * generic YForm manager page.
     */
    public static function currentPageTableName(): ?string
    {
        $tableName = rex_request('table_name', 'string', '');
        if ($tableName !== '') {
            return $tableName;
        }
        return null;
    }

    public static function currentBackendPage(): string
    {
        return rex_be_controller::getCurrentPage();
    }

    /**
     * @return list<array{name: string, label: string, type: string}>
     */
    public static function searchableFields(rex_yform_manager_table $table): array
    {
        $langFieldsAvailable = rex_addon::get('yform_lang_fields')->isAvailable();
        $columns = self::realColumnNames($table->getTableName());

        $fields = [];
        foreach ($table->getFields() as $field) {
            $typeName = $field->getTypeName();
            $name = $field->getName();

            // Some field types (e.g. be_manager_relation) report isSearchable()/a db type
            // while storing their actual value in a *related* table, not as a physical
            // column here — a LIKE against such a "column" would be a fatal SQL error.
            if (!in_array($name, $columns, true)) {
                continue;
            }

            $isNativeSearchable = 'value' === $field->getType()
                && $field->isSearchable()
                && method_exists('rex_yform_value_' . $typeName, 'getSearchFilter');
            $isLangJson = $langFieldsAvailable && in_array($typeName, self::LANG_JSON_FIELD_TYPES, true);

            if (!$isNativeSearchable && !$isLangJson) {
                continue;
            }

            $fields[] = [
                'name' => $name,
                'label' => $field->getLabel(),
                'type' => $typeName,
            ];
        }

        return $fields;
    }

    /**
     * @return list<string>
     */
    private static function realColumnNames(string $tableName): array
    {
        static $cache = [];
        if (isset($cache[$tableName])) {
            return $cache[$tableName];
        }

        $sql = rex_sql::factory();
        $sql->setQuery('SHOW COLUMNS FROM `' . $tableName . '`');

        $columns = [];
        foreach ($sql->getArray() as $row) {
            $columns[] = $row['Field'];
        }

        return $cache[$tableName] = $columns;
    }

    /**
     * Field-type-aware filter descriptors for the optional advanced search UI.
     *
     * @return list<array{name: string, label: string, input: string, options?: array<string,string>}>
     */
    public static function filterableFields(rex_yform_manager_table $table): array
    {
        $columns = self::realColumnNames($table->getTableName());

        $filters = [];
        foreach ($table->getFields() as $field) {
            if (!in_array($field->getName(), $columns, true)) {
                continue;
            }

            $typeName = $field->getTypeName();
            $definition = $field->toArray();

            // Multi-value choice fields store CSV ("1,2") in one column — a plain
            // equality filter can't match those correctly, so they're excluded here.
            $isMultiple = '1' === (string) ($definition['multiple'] ?? '0');

            $input = match (true) {
                $isMultiple => null,
                in_array($typeName, ['choice', 'select'], true) => 'select',
                'checkbox' === $typeName => 'select',
                in_array($typeName, ['date', 'datetime'], true) => $typeName,
                'be_number' === $typeName || 'number' === $typeName => 'number',
                default => null,
            };

            if (null === $input) {
                continue;
            }

            $entry = [
                'name' => $field->getName(),
                'label' => $field->getLabel(),
                'input' => $input,
            ];

            if ('select' === $input) {
                $entry['options'] = 'checkbox' === $typeName
                    ? ['1' => rex_i18n::msg('yes'), '0' => rex_i18n::msg('no')]
                    : self::parseChoiceOptions((string) ($definition['choices'] ?? ''));

                if ([] === $entry['options']) {
                    continue;
                }
            }

            $filters[] = $entry;
        }

        return $filters;
    }

    /**
     * Parses YForm's own `choices` element format: comma-separated `Label=value`
     * pairs, with `\,`/`\=` as escapes — mirrors
     * rex_yform_value_abstract::getArrayFromString() exactly, since that is what
     * the choice/select field type itself uses to build its option list.
     *
     * @return array<string,string> value => label
     */
    private static function parseChoiceOptions(string $raw): array
    {
        if ('' === trim($raw)) {
            return [];
        }

        $rawOptions = preg_split('~(?<!\\\\),~', $raw);

        $options = [];
        foreach ($rawOptions as $option) {
            $parts = preg_split('~(?<!\\\\)=~', $option);
            $label = $parts[0];
            $value = $parts[1] ?? $parts[0];

            $label = str_replace(['\=', '\,'], ['=', ','], $label);
            $value = str_replace(['\=', '\,'], ['=', ','], $value);
            $label = rex_i18n::translate(trim($label), false);

            $options[trim($value)] = trim($label);
        }

        return $options;
    }

    /**
     * Runs a bounded live search for a single table using YForm's own search/list
     * conventions (rex_yform_manager_search-style field filters + getListValue()
     * preview formatting), plus yform_lang_fields JSON_TABLE search when applicable.
     *
     * @param array<string,string> $filters field-name => value, from filterableFields()
     * @return list<array{id:int, preview: array<string,string>, url: string}>
     */
    public static function searchTable(rex_yform_manager_table $table, string $term, array $filters = [], int $limit = 10): array
    {
        $searchableFields = self::searchableFields($table);
        if ($searchableFields === [] && $filters === []) {
            return [];
        }

        $sql = rex_sql::factory();
        $termLike = '%' . $sql->escapeLikeWildcards($term) . '%';

        $orConditions = [];
        $params = [];
        $paramIndex = 0;

        foreach ($searchableFields as $field) {
            if ($term === '') {
                break;
            }
            $paramName = 'qnsearch_' . $paramIndex++;

            if (in_array($field['type'], self::LANG_JSON_FIELD_TYPES, true)) {
                $langCondition = self::buildLangJsonCondition($table, $field['name'], $term, $paramName, $params);
                if ($langCondition !== null) {
                    $orConditions[] = $langCondition;
                }
                continue;
            }

            $orConditions[] = '`' . $field['name'] . '` LIKE :' . $paramName;
            $params[$paramName] = $termLike;
        }

        if ($term !== '' && $orConditions === []) {
            return [];
        }

        $query = $table->query();
        if ($term !== '') {
            $query->whereRaw('(' . implode(' OR ', $orConditions) . ')', $params);
        }

        $filterableColumns = self::realColumnNames($table->getTableName());
        foreach ($filters as $fieldName => $value) {
            if ($value === '' || $value === null || !in_array($fieldName, $filterableColumns, true)) {
                continue;
            }
            $query->where($fieldName, $value);
        }

        $query->limit($limit);

        $results = [];
        foreach ($query->find() as $dataset) {
            $results[] = [
                'id' => $dataset->getId(),
                'preview' => self::buildPreview($table, $dataset),
                'url' => self::buildEditUrl($table, $dataset->getId()),
            ];
        }

        return $results;
    }

    /**
     * @param array<string,mixed> $params written by reference (bound query params)
     */
    private static function buildLangJsonCondition(rex_yform_manager_table $table, string $fieldName, string $term, string $paramPrefix, array &$params): ?string
    {
        $conditions = [];
        $i = 0;
        foreach (rex_clang::getAllIds() as $clangId) {
            $paramName = $paramPrefix . '_' . $i++;
            $params[$paramName] = '%' . $term . '%';
            $conditions[] = 'EXISTS (SELECT 1 FROM JSON_TABLE(`' . $fieldName . '`, \'$[*]\' COLUMNS ('
                . 'clang_id INT PATH \'$.clang_id\', value TEXT PATH \'$.value\'' .
                ')) AS jt WHERE jt.clang_id = ' . (int) $clangId . ' AND jt.value LIKE :' . $paramName . ')';
        }

        return $conditions === [] ? null : '(' . implode(' OR ', $conditions) . ')';
    }

    /** Columns always prioritized + highlighted in the preview, if the table has them. */
    private const DEFAULT_HIGHLIGHT_FIELDS = ['name', 'title', 'cat', 'category'];

    /** Max characters shown per text-ish preview field before truncation ("anreißen"). */
    private const PREVIEW_TEXT_MAX_LENGTH = 90;

    /** Max number of preview fields shown per result. */
    private const PREVIEW_MAX_FIELDS = 5;

    /**
     * @return list<string>
     */
    private static function highlightFieldNames(): array
    {
        $configured = (string) rex_addon::get('quick_navigation')->getConfig('quick_navigation_yform_highlight_fields', '');
        $extra = array_filter(array_map('trim', explode(',', $configured)));

        return array_values(array_unique(array_merge(self::DEFAULT_HIGHLIGHT_FIELDS, $extra)));
    }

    /**
     * @return list<array{name:string, label:string, kind:string, highlighted:bool, text?:string, on?:bool, languages?:list<array{clang_id:int, lang_label:string, value:string}>}>
     */
    private static function buildPreview(rex_yform_manager_table $table, rex_yform_manager_dataset $dataset): array
    {
        $langFieldsAvailable = rex_addon::get('yform_lang_fields')->isAvailable();
        $highlightNames = self::highlightFieldNames();

        $preview = [];
        foreach ($table->getFields() as $field) {
            if ($field->isHiddenInList()) {
                continue;
            }

            $name = $field->getName();
            $typeName = $field->getTypeName();
            $rawValue = $dataset->getValue($name);
            $highlighted = in_array($name, $highlightNames, true);

            if ('checkbox' === $typeName) {
                $preview[] = [
                    'name' => $name,
                    'label' => $field->getLabel(),
                    'kind' => 'boolean',
                    'highlighted' => $highlighted,
                    'on' => '1' === (string) $rawValue || 1 === $rawValue,
                ];
                continue;
            }

            if ($langFieldsAvailable && in_array($typeName, self::LANG_JSON_FIELD_TYPES, true)) {
                $languages = self::decodeLangJson((string) $rawValue);
                if ([] === $languages) {
                    continue;
                }
                foreach ($languages as &$lang) {
                    $lang['value'] = self::truncate($lang['value']);
                }
                unset($lang);
                $preview[] = [
                    'name' => $name,
                    'label' => $field->getLabel(),
                    'kind' => 'lang_json',
                    'highlighted' => $highlighted,
                    'languages' => $languages,
                ];
                continue;
            }

            if (in_array($typeName, ['choice', 'select'], true)) {
                $definition = $field->toArray();
                $options = self::parseChoiceOptions((string) ($definition['choices'] ?? ''));
                $isMultiple = '1' === (string) ($definition['multiple'] ?? '0');
                $values = $isMultiple
                    ? array_filter(explode(',', (string) $rawValue))
                    : [(string) $rawValue];

                $labels = array_map(static fn ($v) => $options[trim($v)] ?? trim($v), $values);
                $text = trim(implode(', ', array_filter($labels)));
                if ('' === $text) {
                    continue;
                }
                $preview[] = [
                    'name' => $name,
                    'label' => $field->getLabel(),
                    'kind' => 'choice',
                    'highlighted' => $highlighted,
                    'text' => $text,
                ];
                continue;
            }

            if ('fields_tagging' === $typeName) {
                $tags = self::decodeTaggingJson((string) $rawValue);
                if ([] === $tags) {
                    continue;
                }
                $preview[] = [
                    'name' => $name,
                    'label' => $field->getLabel(),
                    'kind' => 'tags',
                    'highlighted' => $highlighted,
                    'tags' => $tags,
                ];
                continue;
            }

            $className = 'rex_yform_value_' . $typeName;
            if (!method_exists($className, 'getListValue')) {
                continue;
            }

            // Some field types (e.g. fields_tagging) branch into an inline-edit
            // rendering path when list_editable=1, which dereferences a real
            // rex_yform_list object from params['list'] — force it off here since
            // we only ever want the plain read-only rendering for a preview.
            $safeFieldDefinition = $field->toArray();
            $safeFieldDefinition['list_editable'] = '0';

            try {
                $formatted = $className::getListValue([
                    'list' => null,
                    'field' => $safeFieldDefinition,
                    'value' => $rawValue,
                    'subject' => $rawValue,
                    'format' => 'custom',
                    'escape' => false,
                    'params' => [
                        'field' => $safeFieldDefinition,
                        'fields' => $table->getFields(),
                    ],
                ]);
            } catch (\Throwable) {
                $formatted = is_scalar($rawValue) ? (string) $rawValue : '';
            }

            $text = html_entity_decode(strip_tags((string) $formatted), ENT_QUOTES, 'UTF-8');
            $text = trim(preg_replace('/\s+/', ' ', $text));

            if ($text === '') {
                continue;
            }

            $preview[] = [
                'name' => $name,
                'label' => $field->getLabel(),
                'kind' => 'text',
                'highlighted' => $highlighted,
                'text' => self::truncate($text),
            ];
        }

        // Highlighted fields (name/title/cat/category/admin-configured) always lead.
        usort($preview, static fn (array $a, array $b) => (int) $b['highlighted'] <=> (int) $a['highlighted']);

        return array_slice($preview, 0, self::PREVIEW_MAX_FIELDS);
    }

    private static function truncate(string $text): string
    {
        if (mb_strlen($text) <= self::PREVIEW_TEXT_MAX_LENGTH) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, self::PREVIEW_TEXT_MAX_LENGTH)) . '…';
    }

    /**
     * Decodes a yform_lang_fields JSON column into a per-language breakdown,
     * skipping languages with an empty value.
     *
     * @return list<array{clang_id:int, lang_label:string, value:string}>
     */
    private static function decodeLangJson(string $raw): array
    {
        if ('' === trim($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $result = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry) || !isset($entry['clang_id'])) {
                continue;
            }
            $value = html_entity_decode(strip_tags((string) ($entry['value'] ?? '')), ENT_QUOTES, 'UTF-8');
            $value = trim(preg_replace('/\s+/', ' ', $value));
            if ('' === $value) {
                continue;
            }

            $clangId = (int) $entry['clang_id'];
            $clang = rex_clang::get($clangId);

            $result[] = [
                'clang_id' => $clangId,
                'lang_label' => $clang ? $clang->getCode() : (string) $clangId,
                'value' => $value,
            ];
        }

        return $result;
    }

    /**
     * Decodes a fields_tagging JSON column ([{"text":..., "color":...}, ...])
     * into plain tag descriptors — mirrors the addon's own
     * rex_yform_value_fields_tagging::getListValue() decoding.
     *
     * @return list<array{text:string, color:string}>
     */
    private static function decodeTaggingJson(string $raw): array
    {
        if ('' === trim($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $tags = [];
        foreach ($decoded as $item) {
            if (is_array($item) && isset($item['text']) && '' !== trim((string) $item['text'])) {
                $tags[] = [
                    'text' => (string) $item['text'],
                    'color' => isset($item['color']) ? (string) $item['color'] : '#7f8c8d',
                ];
            }
        }

        return $tags;
    }

    /**
     * Builds an edit link, preferring the currently open addon page if it already
     * shows this table, falling back to the YForm manager standard path otherwise.
     */
    public static function buildEditUrl(rex_yform_manager_table $table, int $id): string
    {
        $currentPage = rex_be_controller::getCurrentPage();
        $currentTableName = rex_request('table_name', 'string', '');

        if ($currentPage !== '' && $currentTableName === $table->getTableName() && $currentPage !== 'yform/manager/data_edit') {
            return rex_url::currentBackendPage([
                'table_name' => $table->getTableName(),
                'data_id' => $id,
                'func' => 'edit',
            ] + rex_csrf_token::factory($table->getCSRFKey())->getUrlParams());
        }

        return rex_yform_manager::url($table->getTableName(), $id);
    }
}
