<?php

	/**
	* procore.json.php
	* @author  	   Adam Lee & Yaakov Albietz - ejectcore.com
	* @copyright   Copyright Eject Core 2009-2010. All rights reserved.
	* @license 	   GPLv3 License http://www.gnu.org/licenses/gpl-3.0.html
	* @credit   	3rd Party Development: Seth Benjamin
	* @package     Pro Core Manager API
	* @version 	   v1.0 Final
	*
	*/	 

	function bootstrap() {
		if(isset($_GET['act'])) {
			include realpath(dirname(__FILE__) . '/../../../') . '/config/config.inc.php';
			include realpath(dirname(__FILE__) . '/../../../') . '/init.php';
			
			if(file_exists('procore.api.php')) {
				include_once 'procore.api.php';
				
				$api       = new ProCoreApi($smarty);
				$action    = isset($_GET['act']) ? $_GET['act'] : FALSE;
				$reference = isset($_GET['reference']) ? $_GET['reference'] : FALSE;
				$data      = isset($_GET['data']) ? $_GET['data'] : FALSE;
				
				if($action !== FALSE) {
					$output = $api->moduleAction($action, $reference, $data);
				} else {
					jsonError('Error: Action not defined.');
				}
				return $output;
			
			} else {
				return jsonError('Fatal Error: Core Manager API could not be located');
			}
		}
	}
	
	function jsonError($msg) {
		return json_encode(array('error' => utf8_encode($msg)));
	}
	
	print bootstrap();