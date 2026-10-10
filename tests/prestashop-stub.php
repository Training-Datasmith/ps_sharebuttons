<?php

namespace PrestaShop\PrestaShop\Core\Module {

interface WidgetInterface
{
    public function renderWidget($hookName, array $configuration);

    public function getWidgetVariables($hookName, array $configuration);
}

}

namespace {

error_reporting(-1);

class Validate
{
    public static function isLoadedObject($object)
    {
        return is_object($object) && $object->id;
    }
}

class Tools
{
    public static function getValue($key, $default_value = false)
    {
        if (!isset($key) || empty($key) || !is_string($key)) {
            return false;
        }

        $value = (isset($_POST[$key]) ? $_POST[$key] : (isset($_GET[$key]) ? $_GET[$key] : $default_value));

        if (is_string($value)) {
            return stripslashes(urldecode(preg_replace('/((\%5C0+)|(\%00+))/i', '', urlencode($value))));
        }

        return $value;
    }

    public static function isSubmit($submit)
    {
        return (
            isset($_POST[$submit]) || isset($_POST[$submit . '_x']) || isset($_POST[$submit . '_y'])
            || isset($_GET[$submit]) || isset($_GET[$submit . '_x']) || isset($_GET[$submit . '_y'])
        );
    }

    public static function redirectAdmin($url)
    {
        throw new SharebuttonsAdminRedirectException($url);
    }

    public static function getAdminTokenLite($tab)
    {
        return 'lite-token-' . $tab;
    }

    public static function strtolower($str)
    {
        if (is_array($str)) {
            return false;
        }
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($str, 'utf-8');
        }

        return strtolower($str);
    }

    public static function strtoupper($str)
    {
        if (is_array($str)) {
            return false;
        }
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($str, 'utf-8');
        }

        return strtoupper($str);
    }
}

class SharebuttonsAdminRedirectException extends Exception
{
    /** @var string */
    public $url;

    public function __construct($url)
    {
        parent::__construct('redirect:' . $url);
        $this->url = $url;
    }
}

class Configuration
{
    /** @var array */
    public static $values = array();

    public static function reset()
    {
        self::$values = array();
    }

    public static function get($key)
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }

        return false;
    }

    public static function updateValue($key, $value)
    {
        self::$values[$key] = $value;
    }
}

class Context
{
    /** @var Context */
    private static $instance;

    /** @var object */
    public $controller;

    /** @var Link */
    public $link;

    /** @var SharebuttonsSmartyStub */
    public $smarty;

    public static function getContext()
    {
        return self::$instance;
    }

    public static function setInstance(Context $context)
    {
        self::$instance = $context;
    }
}

class Link
{
    /** @var string|null */
    public static $lastImageRewrite;

    /** @var string|null */
    public static $lastImageIdArg;

    public function getProductLink($product)
    {
        return Context::getContext()->linkProductUrl;
    }

    public function getImageLink($rewrite, $imageId)
    {
        self::$lastImageRewrite = $rewrite;
        self::$lastImageIdArg = $imageId;

        return Context::getContext()->linkImageUrl;
    }

    public function getAdminLink($controller, $withToken = false)
    {
        $url = 'https://shop.example/admin/index.php?controller=' . $controller;
        if ($withToken) {
            $url .= '&token=with-token';
        }

        return $url;
    }
}

class SharebuttonsSmartyStub
{
    /** @var array|null */
    public $lastAssign;

    public function assign($vars)
    {
        $this->lastAssign = $vars;
    }
}

class Module
{
    public $id;
    public $name;
    public $tab;
    public $author;
    public $version;
    public $need_instance;
    public $ps_versions_compliancy;
    public $bootstrap;
    public $displayName;
    public $description;

    /** @var Context */
    public $context;

    /** @var SharebuttonsSmartyStub */
    public $smarty;

    /** @var bool */
    public static $installReturn = true;

    /** @var array */
    public static $registerHookLog = array();

    /** @var array */
    public static $unregisterHookLog = array();

    /** @var bool */
    public static $registerHookReturn = true;

    /** @var bool */
    public static $unregisterHookReturn = true;

    /** @var array */
    public static $isInstalledMap = array();

    /** @var array */
    public static $getInstanceByNameMap = array();

    /** @var array */
    public static $parentUninstallTargets = array();

    /** @var array */
    public static $clearCacheLog = array();

    /** @var array */
    public static $fetchLog = array();

    /** @var string */
    public static $fetchReturn = 'FETCH_SENTINEL';

    /** @var array */
    public static $translationReturns = array();

    /** @var array */
    public static $translationLog = array();

    /** @var string|null */
    public static $lastConfirmation;

    public function __construct($name = null, $context = null)
    {
        $this->context = $context ? $context : Context::getContext();
        if ($this->context) {
            $this->smarty = $this->context->smarty;
        }
    }

    public function install()
    {
        return self::$installReturn;
    }

    public function registerHook($hook_name, $shop_list = null)
    {
        self::$registerHookLog[] = $hook_name;

        return self::$registerHookReturn;
    }

    public function unregisterHook($hook_id, $shop_list = null)
    {
        self::$unregisterHookLog[] = $hook_id;

        return self::$unregisterHookReturn;
    }

    public static function isInstalled($moduleName)
    {
        return !empty(self::$isInstalledMap[$moduleName]);
    }

    public static function getInstanceByName($moduleName)
    {
        return isset(self::$getInstanceByNameMap[$moduleName])
            ? self::$getInstanceByNameMap[$moduleName]
            : false;
    }

    public function uninstall()
    {
        self::$parentUninstallTargets[] = $this;
    }

    protected function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        $key = (string) $domain . "\0" . (string) $id;
        self::$translationLog[] = array('id' => $id, 'domain' => $domain);

        if (isset(self::$translationReturns[$key])) {
            return self::$translationReturns[$key];
        }

        return '__TRANS__' . $domain . '__' . $id;
    }

    public function displayConfirmation($string)
    {
        self::$lastConfirmation = $string;

        return '<confirmation>' . $string . '</confirmation>';
    }

    protected function _clearCache($template, $cache_id = null, $compile_id = null)
    {
        self::$clearCacheLog[] = $template;
    }

    public function fetch($templatePath, $cache_id = null, $compile_id = null)
    {
        self::$fetchLog[] = $templatePath;

        return self::$fetchReturn;
    }
}

class HelperForm
{
    public $submit_action;
    public $currentIndex;
    public $token;
    public $tpl_vars;

    /** @var mixed */
    public static $lastGenerateFormArg;

    /** @var string|null */
    public static $lastSubmitAction;

    /** @var string|null */
    public static $lastCurrentIndex;

    /** @var string|null */
    public static $lastToken;

    /** @var array|null */
    public static $lastTplVars;

    public function generateForm($fields)
    {
        self::$lastGenerateFormArg = $fields;
        self::$lastSubmitAction = $this->submit_action;
        self::$lastCurrentIndex = $this->currentIndex;
        self::$lastToken = $this->token;
        self::$lastTplVars = $this->tpl_vars;

        return '<form-stub/>';
    }
}

class SharebuttonsControllerStub
{
    /** @var object */
    public $product;

    public function getProduct()
    {
        return $this->product;
    }
}

class SharebuttonsProductStub
{
    public $id;
    public $name;
    public $link_rewrite;

    /** @var mixed */
    public $coverReturn;

    /** @var mixed */
    public $lastCoverProductId;

    public function getCover($id)
    {
        $this->lastCoverProductId = $id;

        return $this->coverReturn;
    }
}

class Socialsharing extends Module
{
    /** @var bool */
    public static $subclassUninstallCalled = false;

    public function uninstall()
    {
        self::$subclassUninstallCalled = true;
        Configuration::updateValue('PS_SC_FACEBOOK', null);
        Configuration::updateValue('PS_SC_TWITTER', null);
        Configuration::updateValue('PS_SC_PINTEREST', null);

        return true;
    }
}

}
