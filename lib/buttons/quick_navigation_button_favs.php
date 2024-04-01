<?php

namespace FriendsOfRedaxo\QuickNavigation\Buttons;

use rex;
use rex_addon;
use rex_category;
use rex_fragment;
use rex_i18n;
use rex_url;

use function count;
use function rex_escape;

class FavsButton implements ButtonInterface
{
    public function get(): string
    {
        $mode = 'structure';
        $userId = rex::getUser()->getId();
        $datas = rex_addon::get('quick_navigation')->getConfig('quicknavi_favs' . $userId);
        if ($datas && count($datas) >= 1) {
            $items = [];
            $clang = rex_request('clang', 'int');
            foreach ($datas as $data) {
                if (rex_category::get($data)) {
                    $cat = rex_category::get($data);
                    $catName = rex_escape($cat->getName());
                    $catId = rex_escape($cat->getId());
                    $href = rex_url::backendPage(
                        'content/edit',
                        [
                            'page' => $mode,
                            'clang' => $clang,
                            'category_id' => $data,
                        ]
                    );
                    $addHref = rex_url::backendPage(
                        'structure',
                        [
                            'category_id' => $catId,
                            'clang' => $clang,
                            'function' => 'add_art',
                        ]
                    );
                    $items[] = '<li class="quicknavi_left"><a href="' . $href . '" title="' . $catName . '">' . $catName . '</a></li>';
                    if ($mode == 'structure') {
                        $items[] = '<li class="quicknavi_right"><a href="' . $addHref . '" title="' . rex_i18n::msg('quicknavi_title_favs') . ' ' .  $catName . '"><i class="fa fa-plus" aria-hidden="true"></i></a></li>';
                    }
                }
            }
            $fragment = new rex_fragment();
            if ($items !== []) {
                $fragment->setVar('items', $items, false);
            }
            $fragment->setVar('icon', 'fa-regular fa-star');
            return $fragment->parse('quick_button.php');
        }
        return '';
    }
}
