	/**
	* module.js
	* @author  	   Adam Lee & Yaakov Albietz - ejectcore.com
	* @copyright   Copyright Eject Core 2009-2010. All rights reserved.
	* @license 	   GPLv3 License http://www.gnu.org/licenses/gpl-3.0.html
	* @credit   	3rd Party Development: Seth Benjamin
	* @package     Pro Core Manager API
	* @version 	   v1.0 Final
	*
	*/	 
	
	var responder = modulePath() + 'libraries/procore.json.php';
	
	function modulePath() {
	  var path = location.pathname.substring(0, (location.pathname.lastIndexOf("/")) + 1).split('/');
	  path.pop();
	  path.pop();
	  return path.join('/') + '/modules/coremanager/';
	}
	
	/******* Pro Core Module *******/
	
	function moduleAction() {
		$('#moduleAction').click(function() {
			var action = $(this).attr('value'), module = $(this).attr('rel');
			$.getJSON(responder + '?act=' + action.toLowerCase() + '&reference=' + module, function(data) {
				alert(data.error);
			});
		});
	}
	
	function transplantModule() {
		$('.transplantModule').click(function() {
			var module = $(this).attr('rel');
			fetchTemplate('transplant', module);
		});
	}
	
	function fetchTemplate(tpl, dataSet) {
		$.getJSON(responder + '?act=template&reference=' + tpl + '&data=' + dataSet, function(data) {
			$(data.content).appendTo('#rightColumn');
		});
	}
	
	$(function() {
		moduleAction();
		transplantModule();
	});