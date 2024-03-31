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
class rex_minibar_element_quicknavi extends rex_minibar_element
{
    public function render()
    {
        // get article history from fragment
        $drophistory = QuickNavigation::get_article_history('minibar', 8);
        return
        '
        <style>
        ul.minibar-quicknavi-items{display:block;font-size:16px;padding:0;margin:0;min-width:300px}.minibar-quicknavi-items li{display:block;margin:0;line-height:16px}.minibar-quicknavi-items small{clear:both;display:block;color:#ccc;font-size:10px}.quicknavi_left{width:88%;display:inline-block}.minibar-quickfiles li a.quicknavi_left.qn_status_1{color:#4b9ad9;border-left:3px solid #3bb594;padding-bottom:10px}.minibar-quickfiles li a.quicknavi_left.qn_status_0{opacity:.6;border-left:3px solid #ccc}.minibar-quickfiles .quicknavi_right{width:12%;display:inline-block}.minibar-quicknavi-items a{color:#fff;text-decoration:none;padding-left:5px;padding-bottom:7px}
        </style>
        <div class="rex-minibar-item">
            <span class="rex-minibar-icon">
                <i class="rex-minibar-icon--fa rex-minibar-icon--fa-clock"></i>
            </span>
            <span class="rex-minibar-value">
            &nbsp;
            </span>
        </div>
        <div class="rex-minibar-info">
            <div class="rex-minibar-info-group">
             '.$drophistory.'
            </div>

        </div>
        ';
    }

    public function getOrientation()
    {
        return rex_minibar_element::LEFT;
    }
}
