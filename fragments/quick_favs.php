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

$drophistory = $date = $link = $where = '';
$user =  rex::getUser()->getId();
$datas = rex_addon::get('quick_navigation')->getConfig('quicknavi_favs'.$user);
if (count($datas)) {

                foreach ($datas as $data) {
				   $cat = rex_category::get($data);
				   $catName = $cat->getName();
                   $attributes = [
                        'href' => rex_url::backendPage('content/edit',
                            [
                                'page' => 'structure',
                                'category_id' => $data
                            ]
                        )
                    ];

                    $link .= '<li><a ' . rex_string::buildAttributes($attributes) . ' title="' . $catName . '">' . $catName .'</a></li>';
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
?>

