<?php

/**
 * procoreapi.php
 * @author       Adam Lee & Yaakov Albietz - ejectcore.com
 * @copyright   Copyright Eject Core 2009-2010. All rights reserved.
 * @license       GPLv3 License http://www.gnu.org/licenses/gpl-3.0.html
 * @credit    3rd Party Development: Seth Benjamin
 * @package     Pro Core Manager API
 * @version       v1.0 Final
 *
 */

class ProCoreApi
{
    public $db;
    public $id_lang;
    protected $self;
    protected $assets;
    /**
     * @var Smarty
     */
    protected $smarty;
    protected $errors = array();
    protected $success = array();

    ## Construct & Destruct
    ##############################################

    function __construct(Smarty $smarty = NULL)
    {
        $cookie = Context::getContext()->cookie;
        $this->db = Db::getInstance();
        $this->smarty = Context::getContext()->smarty;
        $this->id_lang = $cookie->id_lang;
        $this->self = dirname($_SERVER['PHP_SELF']) . '/../modules/coremanager';
        $this->assets = __PS_BASE_URI__ . 'modules/coremanager/assets/';
    }

    function __destruct()
    {
        //     unset($this->db, $this->smarty);
    }

    ## Templates & Translations
    ##############################################

    function view($view, $vars = array(), Smarty $smarty = null, $module = FALSE, $cache_id = NULL)
    {
        if ($smarty = NULL)
            $smarty = Context::getContext()->smarty;

        //	$this->smarty->register_function('l', array('ProCoreApi', 'translate'));
        if (count($vars) > 0) {
            $this->smarty->assign($vars);
        }
        if ($module !== FALSE)
            $template = (realpath(dirname(__FILE__) . '/../') . "/modules/{$module}/views/{$view}.tpl");
        else
            $template = (realpath(dirname(__FILE__) . '/../') . "/views/{$view}.tpl");
        return $this->smarty->createTemplate($template, $cache_id, null, $this->smarty)->fetch();
    }

    static function translate($params, &$smarty)
    {
        global $_LANG, $_MODULES, $cookie, $_MODULE;

        if (!isset($params['js'])) $params['js'] = 0;
        if (!isset($params['mod'])) $params['mod'] = false;

        $msg = FALSE;
        $string = str_replace('\'', '\\\'', $params['s']);

        if (!file_exists(_PS_MODULE_DIR_ . 'coremanager/lang/coremanager.' . $params['mod'] . '.lang.tpl')) {
            $key = $smarty->currentTemplate . '_' . md5($string);
        } else {
            $key = 'coremanager.' . $params['mod'] . '.lang_' . md5($string);
            $params['mod'] = 'coremanager';
        }

        if ($params['mod']) {
            if (file_exists(_PS_THEME_DIR_ . 'modules/' . $params['mod'] . '/' . Language::getIsoById($cookie->id_lang) . '.php')) {
                $translationsFile = _PS_THEME_DIR_ . 'modules/' . $params['mod'] . '/' . Language::getIsoById($cookie->id_lang) . '.php';
                $modKey = '<{' . $params['mod'] . '}' . _THEME_NAME_ . '>' . $key;
            } else {
                $translationsFile = _PS_MODULE_DIR_ . $params['mod'] . '/' . Language::getIsoById($cookie->id_lang) . '.php';
                $modKey = '<{' . $params['mod'] . '}prestashop>' . $key;
            }

            if (@include_once($translationsFile)) $_MODULES = array_merge($_MODULES, $_MODULE);

            $msg = (is_array($_MODULES) AND key_exists($modKey, $_MODULES)) ? ($params['js'] ? addslashes($_MODULES[$modKey]) : stripslashes($_MODULES[$modKey])) : FALSE;
        }

        if (!$msg) $msg = (is_array($_LANG) AND key_exists($key, $_LANG)) ? ($params['js'] ? addslashes($_LANG[$key]) : stripslashes($_LANG[$key])) : $params['s'];

        return ($params['js'] ? $msg : Tools::htmlentitiesUTF8($msg));
    }

    function l($string, $module = FALSE)
    {
        global $_MODULE, $_MODULES;

        if (!class_exists('Module', FALSE) && !class_exists('CoreManager', FALSE)) {
            $path = _PS_MODULE_DIR_ . '../';

            if (!class_exists('Module'))
                include_once $path . '/classes/module/Module.php';
            include_once $path . '/modules/coremanager/coremanager.php';
        }

        if (is_array($_MODULE)) $_MODULES = array_merge($_MODULES, $_MODULE);

        $obj = new CoreManager(TRUE);
        return $obj->l($string, $module === FALSE ? 'procore.api' : 'coremanager.' . preg_replace('/(.module|.admin|.module.json|.admin.json)/', '', $module) . '.lang');
    }

    function updateTranslationFile($module)
    {
        $toWrite = array();
        $views = glob(_PS_MODULE_DIR_ . 'coremanager/modules/' . $module . '/views/*.tpl');

        foreach ($views AS $view) {
            $file = file($view);
            foreach ($file AS $file) {
                if (preg_match_all('/\{l s=' . _PS_TRANS_PATTERN_ . '\}/U', $file, $matches)) {
                    array_shift($matches);
                    foreach ($matches AS $match) {
                        foreach ($match AS $group) {
                            $toWrite[$module][] = "{l s={$group}}";
                        }
                    }
                }
            }
        }

        foreach ($toWrite AS $file => $group) {
            $handle = fopen(_PS_MODULE_DIR_ . 'coremanager/lang/coremanager.' . $module . '.lang.tpl', 'w');
            fwrite($handle, implode("\n", $group));
            fclose($handle);
        }
        return TRUE;
    }

    ## Smarty Extension
    ##############################################

    function trimString($params, &$smarty)
    {
        if (isset($params['string'])) {
            if (strlen($params['string']) > $params['length']) {
                if (!isset($params['ellipsis'])) $params['length'] = $params['length'] - 1;
                $params['string'] = substr($params['string'], 0, $params['length']);
                if (!isset($params['ellipsis'])) $params['string'] = $params['string'] . '&hellip;';
            }
        }

        return $params['string'];
    }

    ## Temporary Session Data
    ##############################################

    function set_flashdata($index, $content)
    {
        $_SESSION[$index . ':new'] = is_array($content) ? serialize($content) : $content;
    }

    function flashdata($index)
    {
        foreach ($_SESSION AS $key => $value) {
            $tmp = explode(':', $key);
            if (isset($tmp[1]) && $tmp[1] == 'old') {
                if (isset($tmp[0]) == $index) $data = $value;
                unset($_SESSION[$key]);
            }
        }

        if (isset($_SESSION[$index . ':new'])) {
            $data = $_SESSION[$index . ':new'];
            unset($_SESSION[$index . ':new']);
            $_SESSION[$index . ':old'] = $data;
        }

        return isset($data) ? $data : FALSE;
    }

    ## Module Control
    ##############################################

    function postProcess(&$errors, &$success, &$data)
    {
        $errors = $success = $data = array();
        $module = isset($_POST['module']) ? $_POST['module'] : '';
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        $show = isset($_GET['show']) ? $_GET['show'] : FALSE;
        $uriAction = isset($_GET['action']) ? $_GET['action'] : FALSE;
        $uriModule = isset($_GET['module']) ? $_GET['module'] : FALSE;
        $uriRef = isset($_GET['reference']) ? $_GET['reference'] : FALSE;

        if ($uriAction && $uriModule && $uriRef) {
            switch ($uriAction) {
                case 'deleteTransplant' :
                    if ($this->deleteTransplant($uriModule, $uriRef)) {
                        $this->set_flashdata('success', 'Transplant successfully deleted');
                    }
                    break;
            }
            header('Location: ' . $this->moduleRedirect($uriModule, 'transplants'));
        }

        if ($show && !$_POST) {
            switch ($show) {
                case 'transplants' :
                    $data['rightColumn'] = $this->view('transplant', array(
                        'selfURL' => $_SERVER['REQUEST_URI'],
                        'assets' => $this->self . '/assets/',
                        'transplants' => $this->getTransplants($uriModule),
                        'transplanted' => $this->getTransplants($uriModule, TRUE),
                        'module' => $uriModule));
                    break;
            }
        }

        if ($_POST) {
            switch ($action) {
                case 'install_module' :
                    if ($this->installModule($module)) $success[] = 'Successfully installed module';
                    break;
                case 'uninstall_module' :
                    if ($this->uninstallModule($module)) $success[] = 'Successfully uninstalled module';
                    break;
                case 'transplant' :
                    if (isset($_POST['doTransplant'])) {
                        if ($this->createTransplant($module)) {
                            $success[] = 'Successfully transplanted module';
                        }
                    }

                    $data['rightColumn'] = $this->view('transplant', array(
                        'selfURL' => $_SERVER['REQUEST_URI'],
                        'assets' => $this->self . '/assets/',
                        'transplants' => $this->getTransplants($module),
                        'transplanted' => $this->getTransplants($module, TRUE),
                        'module' => $module));
                    break;
                default :
                    $errors[] = 'Invalid action';
                    break;
            }
        }

        $flSuccess = $this->flashdata('success');
        if ($flSuccess) $success[] = $flSuccess;

        $errors = array_merge($this->errors, $errors);
        $success = array_merge($this->success, $success);
    }

    function moduleRedirect($who = FALSE, $what = FALSE)
    {
        $url = '';
        $blacklist = array('module', 'reference', 'action', 'show');
        $uri = explode('&', $_SERVER['REQUEST_URI']);

        foreach ($uri AS $uri) {
            $part = explode('=', $uri);
            if (isset($part[0]) && !in_array($part[0], $blacklist)) {
                $url .= '&' . $uri;
            }
        }

        if ($who) $url .= '&module=' . $who;
        if ($what) $url .= '&show=' . $what;

        return str_replace('&/', '/', $url);
    }

    function loadModules($hook, $params = FALSE, $suffix = 'module')
    {
        $toLoad = $objArray = array();
        $hookId = $this->db->getRow('SELECT `id_hook` FROM `' . _DB_PREFIX_ . 'hook` WHERE `name` = "' . str_replace('hook', '', $hook) . '"');
        $output = '';

        if ($hookId) {
            $modules = $this->db->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'coremanager`');

            foreach ($modules AS $module) {
                if (in_array($hookId['id_hook'], explode(',', $module['hook_collation']))) {
                    $toLoad[] = $module;
                }
            }

            foreach ($toLoad AS $module) {
                $file = realpath(dirname(__FILE__) . '/../') . '/modules/' . $module['module_ref'] . '/' . $module['module_ref'] . '.' . $suffix . '.php';

                if (file_exists($file)) {
                    include_once $file;
                    $tmpObj = new $module['module_ref']();

                    if (method_exists($module['module_ref'], $hook)) {
                        array_push($objArray, $tmpObj->$hook($params));
                    }
                }
            }
        }

        foreach ($objArray AS $hook) $output .= $hook;
        return $output;
    }

    function moduleAction($action, $reference, $data)
    {
        switch ($action) {
            case 'template' :
                return json_encode(array('content' => $this->view($reference)));
                break;
            default :
                return json_encode(array('error' => 'Action does not exist.'));
                break;
        }
    }

    function moduleInfo($module, $suffix = 'module')
    {
        if (file_exists($module)) {
            $list = array();
            $objectName = str_replace('.' . $suffix, '', pathinfo($module, PATHINFO_FILENAME));
            include_once $module;
            if (class_exists($objectName, FALSE)) {
                $tmpObj = new $objectName();
                $list = array(
                    'icon' => $this->self . '/modules/' . $objectName . '/' . $objectName . '.gif',
                    'name' => $this->moduleProperty($tmpObj, 'name'),
                    'about' => $this->moduleProperty($tmpObj, 'about'),
                    'version' => $this->moduleProperty($tmpObj, 'version'),
                    'installed' => $this->isModuleInstalled($objectName),
                    'reference' => $objectName
                );
                unset($tmpObj);
            }
        }

        return isset($list) ? $list : array();
    }

    function isModuleInstalled($reference)
    {
        $module = $this->db->getRow('SELECT `id_coremanager` FROM `' . _DB_PREFIX_ . 'coremanager` WHERE `module_ref` = "' . $reference . '"');
        return $module != FALSE ? TRUE : FALSE;
    }

    function moduleProperty($obj, $property)
    {
        return property_exists($obj, $property) ? $obj->{$property} : '';
    }

    function installedModuleId($module)
    {
        $module = $this->db->getRow('SELECT `id_module` FROM `' . _DB_PREFIX_ . 'module` WHERE `name` = "' . $module . '"');
        return $module['id_module'];
    }

    function hookModule($module, $collation)
    {
        return $this->db->Execute('UPDATE `' . _DB_PREFIX_ . 'coremanager` SET `hook_collation` = "' . implode(',', $collation) . '" WHERE `module_ref` = "' . $module . '"');
    }

    function listModules()
    {
        $list = array();
        $modules = glob(realpath(dirname(__FILE__) . '/../') . '/modules/*/*.module.php');

        foreach ($modules AS $module) $list[] = $this->moduleInfo($module);
        return $list;
    }


    function moduleConfiguration($reference, $action = 'getConfig', $config = NULL)
    {
        switch ($action) {
            case 'update':
                return $this->db->Execute('
						UPDATE `' . _DB_PREFIX_ . 'coremanager`
						SET `data` = "' . addslashes(serialize($config)) . '"
						WHERE `module_ref` = "' . $reference . '"
					');
                break;
            case 'getConfig':
                if (is_null($config)) {
                    $configDb = $this->db->getRow('SELECT `data` FROM `' . _DB_PREFIX_ . 'coremanager` WHERE `module_ref` = "' . $reference . '"');
                    $data = unserialize($configDb['data']);
                    return isset($data) ? $data : array();
                }
                break;
        }
    }

    function createTransplant($module)
    {
        $hook = isset($_POST['transplantTo']) ? $_POST['transplantTo'] : FALSE;
        $prevColl = $this->db->getRow('SELECT `hook_collation` FROM `' . _DB_PREFIX_ . 'coremanager` WHERE `module_ref` = "' . $module . '"');
        $collation = explode(',', $prevColl['hook_collation']);
        array_push($collation, $hook);
        return $this->db->Execute('UPDATE `' . _DB_PREFIX_ . 'coremanager` SET `hook_collation` = "' . implode(',', $collation) . '" WHERE `module_ref` = "' . $module . '"');
    }

    function deleteTransplant($module, $reference)
    {
        $prevColl = $this->db->getRow('SELECT `hook_collation` FROM `' . _DB_PREFIX_ . 'coremanager` WHERE `module_ref` = "' . $module . '"');
        $collation = explode(',', $prevColl['hook_collation']);

        foreach ($collation AS $key => $hook) {
            if ($hook == $reference) {
                unset($collation[$key]);
            }
        }

        $newColl = implode(',', array_diff($collation, array('')));
        return $this->db->Execute('UPDATE `' . _DB_PREFIX_ . 'coremanager` SET `hook_collation` = "' . $newColl . '" WHERE `module_ref` = "' . $module . '"');
    }

    function getTransplants($reference, $transplanted = FALSE)
    {
        $hooks = array();
        $exclude = $this->db->getRow('SELECT `hook_collation` FROM `' . _DB_PREFIX_ . 'coremanager` WHERE `module_ref` = "' . $reference . '"');
        $exclude = explode(',', $exclude['hook_collation']);
        $include = $this->db->ExecuteS('SELECT `id_hook`, `name`, `title` FROM `' . _DB_PREFIX_ . 'hook`');

        foreach ($include AS $include) {
            if ($transplanted == FALSE) {
                if (!in_array($include['id_hook'], $exclude)) {
                    $hooks[] = $include;
                }
            } else {
                if (in_array($include['id_hook'], $exclude)) {
                    $hooks[] = $include;
                }
            }
        }

        return $hooks;
    }

    function installModule($reference, $suffix = 'module')
    {
        if ($this->validateModule($reference, $suffix)) {
            include_once realpath(dirname(__FILE__) . '/../') . '/modules/' . $reference . '/' . $reference . '.' . $suffix . '.php';
            $tmpObj = new $reference();

            $this->db->Execute('INSERT INTO `' . _DB_PREFIX_ . 'coremanager` (module_ref) VALUES("' . $reference . '")');
            if (method_exists($tmpObj, 'install')) {
                if ($tmpObj->install()) {
                    return FALSE;
                }
            }
        }

        return FALSE;
    }

    function uninstallModule($reference, $suffix = 'module')
    {
        if ($this->validateModule($reference, $suffix)) {
            include_once realpath(dirname(__FILE__) . '/../') . '/modules/' . $reference . '/' . $reference . '.' . $suffix . '.php';
            $tmpObj = new $reference();

            if (method_exists($tmpObj, 'uninstall')) {
                if ($tmpObj->uninstall()) {
                    return FALSE;
                }
            }

            $this->db->Execute('DELETE FROM `' . _DB_PREFIX_ . 'coremanager` WHERE `module_ref` = "' . $reference . '"');
            return TRUE;
        }

        return FALSE;
    }

    function validateModule($reference, $suffix)
    {
        return file_exists(realpath(dirname(__FILE__) . '/../') . '/modules/' . $reference . '/' . $reference . '.' . $suffix . '.php');
    }

}