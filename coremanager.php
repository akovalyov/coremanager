<?php

/**
 * coremanager.php
 * @author       Adam Lee & Yaakov Albietz - ejectcore.com
 * @copyright   Copyright Eject Core 2009-2010. All rights reserved.
 * @license       GPLv3 License http://www.gnu.org/licenses/gpl-3.0.html
 * @credit    3rd Party Development: Seth Benjamin
 * @package     Pro Core Manager API
 * @version       v1.0 Final
 *
 */

class CoreManager extends Module
{
    /**
     * @var ProCoreApi
     */
    private $apiObj;
    protected $errors = array();
    protected $success = array();
    protected $adminDir = '';
    protected $moduleDir = '';
    protected $basepath = '';

    function __construct($noApi = FALSE)
    {

        global $smarty;

        $this->name = 'coremanager';
        $this->tab = 'ejectcore.com';
        $this->version = '2.1';
        $this->displayName = $this->l('Filter Search Community Edition');
        $this->description = $this->l('Including Pro Core API Module Framework v1.0');
        $this->basepath = realpath(dirname(__FILE__) . '/../../');
        $this->moduleDir = _PS_MODULE_DIR_ . $this->name . '/';

        if ($noApi == FALSE) {
            if (file_exists($this->moduleDir . 'libraries/procore.api.php')) {
                if (session_id() == '') session_start();
                include_once $this->moduleDir . 'libraries/procore.api.php';
                $this->apiObj = new ProCoreApi($smarty);
            }
        }
        //	if($this->moduleStatus($this->name) !== TRUE)
        //$smarty->clear_compiled_tpl();

        parent::__construct();

    }

    function __destruct()
    {
        unset($this->apiObj);
    }

    function __call($hook, $params)
    {


          return $this->apiObj->loadModules($hook, $params);
    }

    function hookdisplayHeader($params)
    {

        $this->context->controller->addJqueryUI('ui.tabs');
        $output = "";
        $output .= "<script type='text/javascript'>var psModulePath = '" . __PS_BASE_URI__ . "'</script>";
        $output .= "<script type='text/javascript'>function modulePath() {	return psModulePath + 'modules/coremanager/'; }</script>";
        $output .= $this->apiObj->loadModules(__FUNCTION__, $params);

        return $output;
    }

    function install()
    {
        set_time_limit(0);
        if (parent::install() == FALSE OR $this->registerToHooks() == FALSE)
            return FALSE;
        $coreInstall = Db::getInstance()->Execute('
				CREATE TABLE ' . _DB_PREFIX_ . 'coremanager (
					`id_coremanager` int(11) unsigned NOT NULL auto_increment,
					`module_ref` varchar(255) NOT NULL,
					`hook_collation` text NOT NULL,
					`data` longtext NULL,
					UNIQUE KEY `module_ref` (`module_ref`),
					KEY `id_module` (`id_coremanager`),
					FULLTEXT KEY `hook_collation` (`hook_collation`)
				) ENGINE=MyISAM default CHARSET=utf8');

        return (bool)($coreInstall . $this->autoInstallModules());

    }

    function autoInstallModules()
    {
        global $cookie;
        $modules = $this->apiObj->listModules();
        foreach ($modules AS $module) {
            $this->apiObj->installModule($module['reference']);
            $this->apiObj->updateTranslationFile($module['reference']);
        }
    }

    function autoUninstallModules()
    {
        $modules = $this->apiObj->listModules();
        foreach ($modules AS $module)
            $this->apiObj->uninstallModule($module['reference']);
    }

    function uninstall()
    {
        $this->autoUninstallModules();
        if (!Db::getInstance()->Execute('DROP TABLE ' . _DB_PREFIX_ . 'coremanager'))
            return FALSE;
        return parent::uninstall();
    }

    function registerToHooks()
    {
        $hooks = $this->apiObj->db->ExecuteS('SELECT `name`, `id_hook` FROM `' . _DB_PREFIX_ . 'hook`');
        foreach ($hooks AS $hook) {
            if (!$this->registerHook($hook['name']))
                return FALSE;
            if ($this->updatePosition($hook['id_hook'], 0, 1))
                $this->cleanPositions(intval($hook['id_hook']));
        }
        return TRUE;
    }

    function moduleStatus($module)
    {
        return (bool)Db::getInstance()->getRow('SELECT `active` FROM `' . _DB_PREFIX_ . 'module` WHERE `name` = "' . $module . '" AND `active` = 1');
    }

    function getContent()
    {
        //	$smarty->currentTemplate = 'coremanager';
        $smarty = Context::getContext()->smarty;
        if (!is_writable($this->moduleDir . 'lang/'))
            array_push($this->errors, $this->l('Lang directory is not writable.'));

        $this->apiObj->postProcess($errors, $success, $data);
        foreach ($errors AS $error) array_push($this->errors, $error);
        foreach ($success AS $success) array_push($this->success, $success);

        return $this->apiObj->view('coremanager', array(
            'errors' => $this->errors,
            'success' => $this->success,
            'assets' => $this->moduleDir . 'assets/',
            'rightColumn' => isset($data['rightColumn']) ? $data['rightColumn'] : '',
            'modules' => $this->apiObj->listModules()), $smarty, false, 'coremanager');
    }
}
