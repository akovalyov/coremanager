<?php
	/**
	* filtersearch.json.php
	* @author  	   Adam Lee & Yaakov Albietz - ejectcore.com
	* @copyright   Copyright Eject Core 2009-2010. All rights reserved.
	* @license 	   GPLv3 License http://www.gnu.org/licenses/gpl-3.0.html
	* @credit   	3rd Party Development: Seth Benjamin
	* @package     Filter Search Community Edition
	* @version 	   v2.1 Final
	*
	*/
	
	function bootstrap() {
		if(isset($_GET['act'])) {
			include realpath(dirname(__FILE__) . '/../../../../') . '/config/config.inc.php';
			include realpath(dirname(__FILE__) . '/../../../../') . '/init.php';
			if(! class_exists('ProCoreApi', FALSE)) include realpath(dirname(__FILE__) . '/../../libraries/') . '/procore.api.php';
			if(! class_exists('FilterSearch', FALSE)) include realpath(dirname(__FILE__) . '/../../modules/') . '/filtersearch/filtersearch.module.php';
			$smarty = Context::getContext()->smarty;
			$api      = new ProCoreApi();
			$filter   = new FilterSearch(TRUE);
			$page     = isset($_GET['page']) ? $_GET['page'] : 1;
			$perpage  = isset($_GET['page']) ? $_GET['perpage'] : 10;
			$orderby  = isset($_GET['orderby']) ? $_GET['orderby'] : 'newest';
			$orderway = isset($_GET['orderway']) ? $_GET['orderway'] : 'desc';
			
			if($_GET['act'] == 'filter') {
				$output = $filter->performSearch($smarty, $page, $perpage, $orderby, $orderway);
			} elseif($_GET['act'] == 'tabs') {
				$output = array('tabs' => $filter->sortByTabs($smarty));
			}
			return json_encode($output);
		}
	}
	print bootstrap();