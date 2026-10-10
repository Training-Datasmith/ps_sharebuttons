<?php

error_reporting(-1);

abstract class SharebuttonsTestCase extends PHPUnit_Framework_TestCase
{
    /** @var Context */
    protected $context;

    /** @var object */
    protected $product;

    /** @var Ps_Sharebuttons */
    protected $module;

    protected function setUp()
    {
        parent::setUp();
        $this->resetEnvironment();
        $this->module = new Ps_Sharebuttons();
        $this->seedProductFixture();
    }

    protected function tearDown()
    {
        $this->resetEnvironment();
        parent::tearDown();
    }

    protected function resetEnvironment()
    {
        $_GET = array();
        $_POST = array();
        Configuration::reset();
        Module::$installReturn = true;
        Module::$registerHookLog = array();
        Module::$unregisterHookLog = array();
        Module::$registerHookReturn = true;
        Module::$unregisterHookReturn = true;
        Module::$isInstalledMap = array();
        Module::$getInstanceByNameMap = array();
        Module::$parentUninstallTargets = array();
        Module::$clearCacheLog = array();
        Module::$fetchLog = array();
        Module::$fetchReturn = 'FETCH_SENTINEL';
        Module::$translationCallCounts = array();
        Module::$translationLog = array();
        Module::$lastConfirmation = null;
        HelperForm::$lastGenerateFormArg = null;
        HelperForm::$lastSubmitAction = null;
        HelperForm::$lastCurrentIndex = null;
        HelperForm::$lastToken = null;
        HelperForm::$lastTplVars = null;
        Link::$lastImageIdArg = null;
        Link::$lastImageRewrite = null;
        Socialsharing::$subclassUninstallCalled = false;

        $this->context = new Context();
        $this->context->smarty = new SharebuttonsSmartyStub();
        $this->context->link = new Link();
        $this->context->linkProductUrl = 'https://shop.example/ceramic-mug';
        $this->context->linkImageUrl = 'https://cdn.example/7/ceramic-mug.jpg';
        Context::setInstance($this->context);
    }

    protected function assertTranslationTokenDomain($token, $domain)
    {
        $this->assertInternalType('string', $token);
        $this->assertStringStartsWith('SENT[' . $domain . ']#', $token);
    }

    protected function seedProductFixture()
    {
        $this->product = new SharebuttonsProductStub();
        $this->product->id = 42;
        $this->product->name = 'Ceramic Mug';
        $this->product->link_rewrite = 'ceramic-mug';
        $this->product->coverReturn = array('id_image' => 7);
        $this->setControllerProduct($this->product);
    }

    /**
     * @param object $product
     */
    protected function setControllerProduct($product)
    {
        $controller = new SharebuttonsControllerStub();
        $controller->product = $product;
        $this->context->controller = $controller;
    }

    protected function enableAllNetworks()
    {
        Configuration::updateValue('PS_SC_FACEBOOK', 1);
        Configuration::updateValue('PS_SC_TWITTER', 1);
        Configuration::updateValue('PS_SC_PINTEREST', 1);
    }

    /**
     * @param string $scriptPath absolute path
     * @param string $wrapper absolute path to php file to run
     * @return array{exit:int,stdout:string,stderr:string}
     */
    protected function runPhpChild($wrapper)
    {
        $php = PHP_BINARY;
        $descriptorSpec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $cmd = escapeshellarg($php)
            . ' -d error_reporting=-1 -d display_errors=stderr -d display_startup_errors=1 -d log_errors=0 '
            . escapeshellarg($wrapper);
        $process = proc_open($cmd, $descriptorSpec, $pipes, dirname(dirname(__FILE__)));
        if (!is_resource($process)) {
            $this->fail('proc_open failed');
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        $this->assertNoPhpDiagnostics($stdout, $stderr);

        return array('exit' => $exit, 'stdout' => $stdout, 'stderr' => $stderr);
    }

    protected function assertNoPhpDiagnostics($stdout, $stderr)
    {
        $combined = $stdout . $stderr;
        $patterns = array(
            'Notice:',
            'Warning:',
            'Deprecated:',
            'Strict Standards:',
            'Parse error:',
            'Fatal error:',
            'Catchable fatal error:',
            'Recoverable fatal error:',
        );
        foreach ($patterns as $pattern) {
            $this->assertNotContains($pattern, $combined, 'PHP diagnostic: ' . $pattern);
        }
    }

    protected function parseQueryUrl($url)
    {
        $parts = parse_url($url);
        $this->assertNotFalse($parts);
        $query = array();
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        return array('parts' => $parts, 'query' => $query);
    }
}
