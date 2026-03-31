<?php

namespace FriendsOfRedaxo\QuickNavigation\Button;

use function count;

use rex_clang;
use rex_fragment;
use rex_i18n;
use rex_request;
use rex_string;
use rex_url;

class ClangButton implements ButtonInterface
{
    public function get(): string
    {
        $clangs = rex_clang::getAll();

        if (count($clangs) < 2) {
            return '';
        }

        $currentClangId = rex_clang::getCurrentId();

        // Alle relevanten Linkmap-Parameter aus dem aktuellen Request erhalten
        $page = rex_request('page', 'string', 'linkmap');
        $persistParams = [];
        $openerInputField = rex_request('opener_input_field', 'string', '');
        if ('' !== $openerInputField) {
            $persistParams['opener_input_field'] = $openerInputField;
        }
        $categoryId = rex_request('category_id', 'int', -1);
        if ($categoryId >= 0) {
            $persistParams['category_id'] = $categoryId;
        }
        $articleId = rex_request('article_id', 'int', 0);
        if ($articleId > 0) {
            $persistParams['article_id'] = $articleId;
        }
        $function = rex_request('function', 'string', '');
        if ('' !== $function) {
            $persistParams['function'] = $function;
        }

        $listItems = [];

        foreach ($clangs as $clang) {
            $clangId = $clang->getId();
            $isActive = $clangId === $currentClangId;

            $params = array_merge($persistParams, ['clang' => $clangId]);

            $attributes = [
                'href' => rex_url::backendPage($page, $params),
                'class' => $isActive ? 'quick-navigation-current' : '',
            ];

            $listItems[] = '<a' . rex_string::buildAttributes($attributes) . '>'
                . '<strong>' . rex_escape(strtoupper($clang->getCode())) . '</strong>'
                . ' <small class="rex-primary-id">' . rex_escape($clang->getName()) . '</small>'
                . '</a>';
        }

        $currentClang = rex_clang::get($currentClangId);
        $currentCode = $currentClang ? strtoupper($currentClang->getCode()) : (string) $currentClangId;
        $currentName = $currentClang ? $currentClang->getName() : (string) $currentClangId;

        $fragment = new rex_fragment([
            'badge' => $currentCode,
            'label' => rex_i18n::msg('quick_navigation_clang') . ': ' . $currentName,
            'icon' => 'fa-solid fa-language',
            'listItems' => $listItems,
            'listType' => 'list',
        ]);

        return $fragment->parse('QuickNavigation/Dropdown.php');
    }
}
