
	/**
	* install.sql
	* @author  	   Adam Lee & Yaakov Albietz - ejectcore.com
	* @copyright   Copyright Eject Core 2009-2010. All rights reserved.
	* @license 	   GPLv3 License http://www.gnu.org/licenses/gpl-3.0.html
	* @credit   	3rd Party Development: Seth Benjamin
	* @package     Filter Search Community Edition
	* @version 	   v2.1 Final
	*
	*/

	CREATE TABLE IF NOT EXISTS `PREFIX_filter_group` (
	  `id_filter_group` int(10) unsigned NOT NULL,
	  `object` varchar(128) NOT NULL,  
	  `global` tinyint(1) unsigned NOT NULL,
	  `selected` tinyint(1) unsigned NOT NULL,
	  `position` int(10) unsigned NOT NULL default '0',
	  PRIMARY KEY  (`id_filter_group`),
	  KEY `filter_group_index` (`id_filter_group`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	
	/* ################################## */
	/* 		 INSTALL SAMPLE DATA			  */
	/* ################################## */
	
	INSERT INTO `PREFIX_filter_group` VALUES ('2', 'attribute_group', '1', '1', '2');
	INSERT INTO `PREFIX_filter_group` VALUES ('3', 'manufacturer', '1', '1', '3');
	INSERT INTO `PREFIX_filter_group` VALUES ('4', 'feature', '1', '1', '4');