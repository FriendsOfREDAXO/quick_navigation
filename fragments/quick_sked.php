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



$skeds = $categoryId = $filter_date = $skedID = $link = $start = $addLink = $filter_date = $today = $halfayear = '';

$filter_date	= ("Y-m-d");
$categoryId 	= null;
$start      	= date("Y-m-d");
$today			= strtotime($start);
$halfayear		= strtotime('+ 2 month', $today);
$filter_date	= date("Y-m-d", $halfayear);


$skeds =  \Sked\Handler\SkedHandler::getEntries($start, $filter_date, false, 'SORT_ASC', $categoryId);
//dump($skeds);

if (count($skeds)) {
			foreach($skeds as $sked) {
			    	$skedId 				= rex_escape($sked['id']);
					$sked_entry 			= rex_escape($sked['entry']);
					$sked_name				= rex_escape($sked_entry->entry_name);
					$sked_start_date		= rex_escape(rex_formatter::strftime(strtotime($sked_entry->entry_start_date->format('d.m.Y')), 'date'));
					$sked_end_date  		= rex_escape(rex_formatter::strftime(strtotime($sked_entry->entry_end_date->format('d.m.Y')), 'date'));
					$entry_start_time   	= $sked_entry->entry_start_time;
					$entry_start_time_date	= new DateTime($entry_start_time);
					$sked_start_time        = rex_escape($entry_start_time_date->format('H:i'));
		    
		    		$entry_end_time     	= $sked_entry->entry_end_time;
		    		$entry_end_time_date	= new DateTime($entry_end_time);
		    		$sked_end_time          = rex_escape($entry_end_time_date->format('H:i'));
		    		
		    		$sked_color 			= rex_escape($sked_entry->category_color);


			    	$href = rex_url::backendPage('sked/entries',
                            [
                                'func' => 'edit',
                                'id' => $skedId
                            ]
                        );



					$link .= '<li class="sked_border" style="border-color:'.$sked_color.'"><a href="' . $href . '" title="' . $sked_name  . '">' . $sked_name .'<small>' . $sked_start_date . ' bis ' . $sked_end_date . ' - ' . $sked_start_time . ' bis ' . $sked_end_time .'</small></a></li>';

				}
			//		$addLink .= '<li class="quicknavi_right"><a type="button" class="btn btn-default' . rex_string::buildAttributes($addAtrributes) . ' title="'. $this->i18n("sked_add_new_entry") .'"><i class="fa fa-plus" aria-hidden="true"></i></a></li><li class="quicknavi_left"><a ' . rex_string::buildAttributes($addAtrributes) . ' title="'. $this->i18n("sked_add_new_entry") .'">'.$this->i18n("sked_add_new_entry").'</a>';
?>
		   
<?php  
}
					$href = rex_url::backendPage('sked/entries',
                            [
                                'func' => 'add'
                            ]
                        );
					$addLink .= '<li class=""><a class="btn btn-default" href="' . $href . '" title="'. $this->i18n("sked_add_new_entry") .'"><i class="fa fa-plus" aria-hidden="true"> &nbsp&nbsp'.$this->i18n("sked_add_new_entry").'</i></a></li>';


?>
             <div class="btn-group">
		                    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
		                        <i class="fa fa-calendar" aria-hidden="true"></i>
		                        <span class="caret"></span>
		                    </button>
		                    <ul class="quickfiles quicknavi dropdown-menu dropdown-menu-right">
		                    	<?= $addLink ?>
		                        <?= $link ?>
		                    </ul>
		                </div>
