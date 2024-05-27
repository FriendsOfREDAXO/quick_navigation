<?php
/**
 * This file is part of the Quick Navigation package.
 *
 * @author (c) Friends Of REDAXO
 * @author <friendsof@redaxo.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FriendsOfRedaxo\QuickNavigation\Minibar;

use FriendsOfRedaxo\QuickNavigation\Button\ArticleHistoryButton;
use rex_i18n;
use rex_minibar_element;
use rex_response;

class ArticleHistoryElement extends rex_minibar_element
{
    public function render(): string
    {
        // get article history from fragment
        $history = new ArticleHistoryButton('minibar', 10);

        return '
            <style nonce="' . rex_response::getNonce() . '">
                .quick-navigation-minibar-item {
                    min-width: unset;
                }
                .quick-navigation-minibar-item .rex-minibar-icon > i {
                    line-height: 36px;
                }
                
                .quick-navigation-minibar-info span.title {
                    min-width: unset;
                    padding-top: 8px;
                }
                .quick-navigation-minibar-info .rex-minibar-info-group + .rex-minibar-info-group {
                    margin-top: 5px;
                    padding-top: 5px;
                }
                .quick-navigation-minibar-info .rex-minibar-info-piece div {
                    padding-top: 3px;
                    color: #9ca5b2;            
                }
            </style>
            <div class="rex-minibar-item quick-navigation-minibar-item">
                <span class="rex-minibar-icon">
                    <i class="rex-minibar-icon--fa rex-minibar-icon--fa-clock"></i>
                </span>
            </div>
            <div class="rex-minibar-info quick-navigation-minibar-info">
                <div class="rex-minibar-info-header">' . rex_i18n::msg('quick_navigation_article_history') . '</div>
                ' . $history->get() . '
            </div>
            ';
    }

    public function getOrientation(): string
    {
        return rex_minibar_element::LEFT;
    }
}
