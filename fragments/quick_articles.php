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

$drophistory = $date = $name = $mode = $link = $where = $domaintitle = '';
$status_css = ' qn_online';
$mode = $this->mode;
if (!$this->mode)
    {
    $mode ='structure';
}
else
{
    $mode = $this->mode;
}

if (rex::getUser()->hasPerm('quick_navigation[history]')) {
	$were ='';
	if (!rex::getUser()->hasPerm('quick_navigation[all_changes]')) {
		$where = 'WHERE updateuser="'.rex::getUser()->getValue('login').'"';
	}

	$qry = 'SELECT id, status, parent_id, clang_id, startarticle, name, updateuser, updatedate
                    FROM ' . rex::getTable('article') . ' 
                    '. $where .' 
                    ORDER BY updatedate DESC
                    LIMIT '.$this->limit;
	$datas = rex_sql::factory()->getArray($qry);

	if (!count($datas)) {
		$link .= '<li class="alert">'.rex_i18n::msg('quick_navigation_no_entries').'</li>';
	}

	if (count($datas)) {
		foreach ($datas as $data) {
			$dataID = rex_escape($data['id']);
			$lang = rex_clang::get($data['clang_id']);
			$langcode = $lang->getCode();
			if ($langcode) {
				$langcode = '<i class="fa fa-flag-o" aria-hidden="true"></i> ' . $langcode . ' - ';
			}
			$name = rex_escape($data['name']);
			$date = rex_formatter::strftime(strtotime($data['updatedate']), 'datetime');

			if ($mode =='linkmap') {
				$href = "javascript:insertLink('redaxo://".$dataID."','".$name." [".$dataID."]');";
			} 
			else {
				$href = rex_url::backendPage(
					'content/edit',
					[
					'mode' => 'edit',
					'clang' => $data['clang_id'],
					'article_id' => $data['id']
				]
				);
			}

			if (rex_addon::get('yrewrite')->isAvailable()) {
				if (rex_yrewrite::getDomainByArticleId($data['id'])!="" and count(rex_yrewrite::getDomains())>2) {
					$domain = rex_yrewrite::getDomainByArticleId($data['id']);
					$domaintitle = '<br><i class="fa fa-globe" aria-hidden="true"></i> '.rex_escape($domain);
				}
			}

			if ($data['status']== '0')
			{
				$status_css = " qn_offline";
			}
			$link .= '<li class=""><a class="quicknavi_left '. $status_css .'" href="' . $href . '" title="' . $name . '">' . $name . '<small>' . $langcode . '<i class="fa fa-user" aria-hidden="true"></i> ' . rex_escape($data['updateuser']) . ' - ' . $date . $domaintitle . '</small></a>';
			$link .= '<span class="quicknavi_right"><a class ="'. $status_css .'" href="'.rex_getUrl($dataID).'" title="'.  $name . ' '. $this->i18n("title_eye") .'" target="blank"><i class="fa fa-eye" aria-hidden="true"></i></a></span></li>';
			$status_css = ' qn_online';
		}
	}
	?>

		<div class="btn-group">
		<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
		<i class="fa fa-clock-o" aria-hidden="true"></i>
		<span class="caret"></span>
		</button>
		<ul class="quickfiles quicknavi dropdown-menu dropdown-menu-right">
		<?= $link ?>
		</ul>
		</div>
		<?php
}
