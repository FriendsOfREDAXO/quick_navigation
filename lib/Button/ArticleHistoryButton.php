<?php

namespace FriendsOfRedaxo\QuickNavigation\Button;

use IntlDateFormatter;
use rex;
use rex_addon;
use rex_clang;
use rex_formatter;
use rex_fragment;
use rex_i18n;
use rex_sql;
use rex_string;
use rex_url;
use rex_yrewrite;

use function count;

class ArticleHistoryButton implements ButtonInterface
{
    protected string $mode;

    protected int $limit;

    public function __construct(string $mode = 'structure', int $limit = 15)
    {
        // Initialisiere die Klassenvariablen mit den übergebenen Parametern
        $this->mode = $mode;
        $this->limit = $limit;
    }

    public function get(): string
    {
        $where = '';
        $whereParams = [];
        $iconPrefix = $this->mode === 'minibar' ? 'rex-minibar-icon--fa rex-minibar-icon--' : 'fa ';


        if (!rex::getUser()->hasPerm('quick_navigation[history]')) {
            return '';
        }

        if (!rex::getUser()->hasPerm('quick_navigation[all_changes]')) {
            $where = 'WHERE updateuser = :user';
            $whereParams['user'] = rex::getUser()->getValue('login');
        }

        $qry = 'SELECT id, status, parent_id, clang_id, startarticle, name, updateuser, updatedate
                FROM ' . rex::getTable('article') . '
                ' . $where . '
                ORDER BY updatedate DESC
                LIMIT ' . $this->limit;
        $datas = rex_sql::factory()->getArray($qry, $whereParams);

        $listItems = [];
        if (count($datas) > 0) {
            foreach ($datas as $data) {
                $dataID = rex_escape($data['id']);
                $langcode = '';
                $lang = rex_clang::get($data['clang_id']);
                if ($lang && $langcode = $lang->getCode()) {
                    $langcode = '<i class="' . $iconPrefix . 'fa-flag" aria-hidden="true"></i> ' . $langcode . ' - ';
                }

                $name = rex_escape($data['name']);
                $date = rex_formatter::intlDateTime($data['updatedate'], IntlDateFormatter::SHORT);

                $attributesBackend = [
                    'class' => 'quick-navigation-status-' . $data['status'],
                    'href' => rex_url::backendPage('content/edit', ['mode' => 'edit', 'clang' => $data['clang_id'], 'category_id' => $data['parent_id'], 'article_id' => $data['id']]),
                    'title' => $data['name'],
                ];

                $attributesFrontend = [
                    'class' => 'quick-navigation-status-' . $data['status'],
                    'href' => rex_getUrl($data['id']),
                    'title' => $data['name'] . ' ' . rex_i18n::msg('title_eye'),
                    'target' => '_blank',
                ];

                if ($this->mode === 'linkmap') {
                    $attributesBackend['href'] = "javascript:insertLink('redaxo://" . $dataID . "','" . $name . ' [' . $dataID . "]');";
                }


                $domainTitle = '';
                if (rex_addon::get('yrewrite')->isAvailable() && count(rex_yrewrite::getDomains()) > 2) {
                    $domain = rex_yrewrite::getDomainByArticleId($data['id']);
                    if ($domain) {
                        $domainTitle = ' - <i class="' . $iconPrefix . 'fa-solid fa-globe" aria-hidden="true"></i> ' . rex_escape($domain);
                    }
                }

                if ($this->mode === 'minibar') {
                    $listItem = '
                        <span class="title">
                            <a' . rex_string::buildAttributes($attributesFrontend) . '>
                                <i class="' . $iconPrefix . 'fa-eye" aria-hidden="true"></i>
                            </a>
                        </span>
                        <span>
                            <a' . rex_string::buildAttributes($attributesBackend) . '>
                                ' . $name . '
                            </a>
                            <div>
                                <small>
                                    ' . $langcode . '
                                    <i class="' . $iconPrefix . 'fa-user" aria-hidden="true"></i> 
                                    ' . rex_escape($data['updateuser']) . ' - ' . $date . $domainTitle . '
                                </small>
                            </div>                        
                        </span>
                    ';
                } else {
                    $listItem = '
                        <div class="quick-navigation-item-row">
                            <a' . rex_string::buildAttributes($attributesBackend) . '>
                                ' . $name . '
                            </a>
                            <a' . rex_string::buildAttributes($attributesFrontend) . '>
                                <i class="' . $iconPrefix . 'fa-eye" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="quick-navigation-item-row">
                            <div class="quick-navigation-item-info">
                                <small>
                                    ' . $langcode . '
                                    <i class="' . $iconPrefix . 'fa-user" aria-hidden="true"></i> 
                                    ' . rex_escape($data['updateuser']) . ' - ' . $date . $domainTitle . '
                                </small>
                            </div>
                        </div>
                    ';
                }

                $listItems[] = $listItem;
            }
        } else {
            $fragment = new rex_fragment();
            $listItems[] = $fragment->parse('QuickNavigation/NoResult.php');
        }

        if ($this->mode === 'minibar') {
            $fragment = new rex_fragment([
                'listItems' => $listItems,
            ]);
            return $fragment->parse('QuickNavigation/MinibarList.php');
        }

        $fragment = new rex_fragment([
            'label' => rex_i18n::msg('quick_navigation_article_history'),
            'icon' => 'fa-regular fa-clock',
            'listItems' => $listItems,
        ]);
        return $fragment->parse('QuickNavigation/Dropdown.php');
    }
}
