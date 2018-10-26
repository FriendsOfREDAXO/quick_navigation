<?php

	/**
 * This file is part of the Quick Navigation package.
 *
 * @author (c) Friends Of REDAXO
 * @author     <friendsof@redaxo.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
	class QuickNavigation
{
	// Media History
	public static function media_history($ep)
	{
		// get media history from fragment
		if (rex_be_controller::getCurrentPagePart(1) == 'mediapool') {       
			$subject = $ep->getSubject();
			$drophistory = new rex_fragment();
			$drophistory->setVar('limit', '15');
			$drophistory = $drophistory->parse('quick_media.php');
			$custom_media_buttons = $custom_media = '';
			$custom_media_buttons = rex_extension::registerPoint(new rex_extension_point('QUICK_NAVI_CUSTOM_MEDIA', $custom_media));
			$button = $custom_media_buttons.'<div class="input-group-btn quickmedia clearfix">'.$drophistory . '</div><select name="rex_file_category"';
			$output = str_replace('<select name="rex_file_category"', $button, $subject);
			return $output;
		}
	}

	// linkmap catlist
	public static function linkmap_list($ep)
	{
		// get catlist history from fragment
		if (rex_be_controller::getCurrentPagePart(1) == 'linkmap') {  
			$droplist = $favs = $drophistory = $qlang = '';    
			// Check if language is set 
			$qlang = rex_request('clang', 'int');
			if ($qlang == 0 || $qlang =='' ) {
				$qlang = 1;
			}
			// get complete quick navi cats from fragment
			$droplist = new rex_fragment();
			$droplist->setVar('mode', 'linkmap');
			$droplist = $droplist->parse('quick_cats.php');                

			// get favs from fragment
			$favs = new rex_fragment();
			$favs->setVar('mode', 'linkmap');
			$favs->setVar('clang', $qlang);
			$favs = $favs->parse('quick_favs.php');

			// get article history from fragment
			$drophistory = new rex_fragment();
			$drophistory->setVar('limit', '15');
			$drophistory->setVar('mode', 'linkmap');
			$drophistory = $drophistory->parse('quick_articles.php');

		        // generate output ep for custom buttons after default set.
		        $custom_linkmap_buttons = $custom ='';
		        $custom_linkmap_buttons = rex_extension::registerPoint(new rex_extension_point('QUICK_LINKMAP_CUSTOM', $custom));

			return '<div class="btn-group quicknavi-btn-group linkmapbt pull-right">' . $droplist . $drophistory . $favs . $custom_linkmap_buttons . '</div>' . $ep->getSubject();
		}
	}

	public static function get()
	{
		$qn_user =  rex::getUser()->getId();

		// get requested language
		$qlang= rex_request('clang', 'int');
		if ($qlang==0) {
			$qlang = 1;
		}

		// AddOn specific :: get data from sked AddOn from fragment
		$dropsked = '';
		$sked_datas = rex_addon::get('quick_navigation')->getConfig('quicknavi_sked'.$qn_user);
		if ($sked_datas != '1') {
			if (rex_addon::get('sked')->isAvailable() && rex::getUser()->hasPerm('sked[]')) {
				$dropsked = new rex_fragment();
				$dropsked = $dropsked->parse('quick_sked.php');
			}
		}

		// AddOn specific :: get data from yForm AddOn from fragment
		$dropyform = '';
		if (rex_addon::get('yform')->isAvailable()) {
			$dropyform = new rex_fragment();
			$dropyform = $dropyform->parse('quick_yform.php');
		}

		// AddOn specific :: set watson button if addon is available and button is active
		$watson = '';
		if (rex_addon::get('watson')->isAvailable() and rex_config::get('watson', 'toggleButton', 0)==1) {
			$watson = '<div class="btn-group"><button class="btn btn-default watson-btn">Watson</button></div>';
		}

		// get favorites from fragment
		$dropfavs = '';
		$dropfavs = new rex_fragment();
		$dropfavs->setVar('clang', $qlang);
		$dropfavs->setVar('mode', 'structure');
		$dropfavs = $dropfavs->parse('quick_favs.php');

		// get complete quick navi cats from fragment 
		$droplist = '';
		$droplist = new rex_fragment();
		$droplist->setVar('mode', 'structure');
		$droplist = $droplist->parse('quick_cats.php');

		// get article history from fragment
		$drophistory = new rex_fragment();
		$drophistory->setVar('limit', '15');
		$drophistory->setVar('mode', 'structure');
		$drophistory = $drophistory->parse('quick_articles.php');

		// generate output ep for custom buttons after default set.
		$custom_buttons = $custom ='';
		$custom_buttons = rex_extension::registerPoint(new rex_extension_point('QUICK_NAVI_CUSTOM', $custom));

		// Output
		return '<div class="btn-group quicknavi-btn-group transparent pull-right">' . $watson . $droplist . $drophistory . $dropyform . $dropsked . $dropfavs . $custom_buttons . '</div>';
	}
}
