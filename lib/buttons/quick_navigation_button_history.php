<?php

namespace FriendsOfRedaxo\QuickNavigation\Buttons;

use rex;
use rex_addon;
use rex_clang;
use rex_formatter;
use rex_fragment;
use rex_i18n;
use rex_sql;
use rex_url;
use rex_yrewrite;

use function count;

class ArticleHistory implements ButtonInterface
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
        $date = $name = $link = $minibar = $where = $domaintitle = $status_css = '';

        if ($this->mode == 'minibar') {
            $icon_prefix = 'rex-minibar-icon--fa rex-minibar-icon--';
        } else {
            $icon_prefix = 'fa ';
        }
        if (rex::getUser()->hasPerm('quick_navigation[history]')) {
            $were = '';
            if (!rex::getUser()->hasPerm('quick_navigation[all_changes]')) {
                $where = 'WHERE updateuser="' . rex::getUser()->getValue('login') . '"';
            }

            $qry = 'SELECT id, status, parent_id, clang_id, startarticle, name, updateuser, updatedate
                    FROM ' . rex::getTable('article') . '
                    ' . $where . '
                    ORDER BY updatedate DESC
                    LIMIT ' . $this->limit;
            $datas = rex_sql::factory()->getArray($qry);

            if (!count($datas)) {
                $link .= '<li class="alert">' . rex_i18n::msg('quick_navigation_no_entries') . '</li>';
            }

            $links = [];
            if (count($datas)) {
                foreach ($datas as $data) {
                    $dataID = rex_escape($data['id']);
                    $langcode = '';
                    $lang = rex_clang::get($data['clang_id']);
                    if ($lang !== null) {
                        $langcode = $lang->getCode();
                        if ($langcode) {
                            $langcode = '<i class="' . $icon_prefix . 'fa-flag" aria-hidden="true"></i> ' . $langcode . ' - ';
                        }
                    }
                    $name = rex_escape($data['name']);
                    $date = rex_formatter::intlDateTime($data['updatedate']);
                    if ($this->mode == 'linkmap') {
                        $href = "javascript:insertLink('redaxo://" . $dataID . "','" . $name . ' [' . $dataID . "]');";
                    } else {
                        $href = rex_url::backendPage(
                            'content/edit',
                            [
                                'mode' => 'edit',
                                'clang' => $data['clang_id'],
                                'category_id' => $data['parent_id'],
                                'article_id' => $data['id'],
                            ]
                        );
                    }

                    if (rex_addon::get('yrewrite')->isAvailable()) {
                        if (count(rex_yrewrite::getDomains()) > 2) {
                            $domain = rex_yrewrite::getDomainByArticleId($data['id']);
                            if ($domain) {
                                $domaintitle = '<br><i class="' . $icon_prefix . 'fa-globe" aria-hidden="true"></i> ' . rex_escape($domain);
                            }
                        }
                    }
                    $status_css = ' qn_status_' . $data['status'];
                    $link .= '<li class=""><a class="quicknavi_left ' . $status_css . '" href="' . $href . '" title="' . $name . '">' . $name . '<small>' . $langcode . '<i class="' . $icon_prefix . 'fa-user" aria-hidden="true"></i> ' . rex_escape($data['updateuser']) . ' - ' . $date . $domaintitle . '</small></a>';
                    $link .= '<span class="quicknavi_right"><a class ="' . $status_css . '" href="' . rex_getUrl($dataID) . '" title="' . $name . ' ' . rex_i18n::msg('quick_navigation_title_eye') . '" target="blank"><i class="' . $icon_prefix . 'fa-eye" aria-hidden="true"></i></a></span></li>';
                    $links[] = $link;
                    $minibar .= $link;
                    $link = '';
                }
            }
            if ($this->mode != 'minibar') {
                $fragment = new rex_fragment();
                $fragment->setVar('items', $links, false);
                $fragment->setVar('icon', 'fa fa-clock');
                return $fragment->parse('quick_button.php');
            }
            return '<ul class="minibar-quicknavi-items">
            ' . $minibar . '
        </ul>';
        }
        return '';
    }
}
