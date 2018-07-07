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

$drophistory = $mode = $date = $link = $where = '';

if (!$this->mode;)
    {
    $mode ='structure';
}
else
{
    $mode = $this->mode;
}

$user =  rex::getUser()->getId();
$datas = rex_addon::get('quick_navigation')->getConfig('quicknavi_favs'.$user);
 if ($datas && count($datas) >= 1) {
    foreach ($datas as $data) {
        if (rex_category::get($data)) {
            $cat = rex_category::get($data);
            $catName = rex_escape($cat->getName());
            $catId = rex_escape($cat->getId());
            $href = rex_url::backendPage(
                'content/edit',
                [
                  'page' => $mode,
                  'clang' => $this->clang,
                  'category_id' => $data
                ]
            );
            $addHref = rex_url::backendPage(
                'structure',
                [
                  'category_id' => $catId,
                  'clang' => $this->clang,
                  'function' => 'add_art'
                ]
            );
            $link .= '<li class="quicknavi_left"><a href="' . $href . '" title="' . $catName . '">' . $catName .'</a></li>';
            if ($mode =='structure') {
                $link .= '<li class="quicknavi_right"><a href="' . $addHref . '" title="'. $this->i18n("title_favs") .' '.  $catName . '"><i class="fa fa-plus" aria-hidden="true"></i></a></li>';
            } 
        }
    }

?>
                <div class="btn-group">
                    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fa fa-star" aria-hidden="true"></i>
                        <span class="caret"></span>
                    </button>
                    <ul class="quickfiles quicknavi dropdown-menu dropdown-menu-right">
                        <?= $link ?>
                    </ul>
                </div>
<?php
}

