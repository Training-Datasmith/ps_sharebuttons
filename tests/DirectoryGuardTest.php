<?php

class DirectoryGuardTest extends SharebuttonsTestCase
{
    private static $guardPaths = array(
        'index.php',
        'views/index.php',
        'views/templates/index.php',
        'views/templates/hook/index.php',
        'upgrade/index.php',
        'tests/index.php',
        'tests/phpstan/index.php',
    );

    public function testRootIndexExitsBeforeSentinel()
    {
        $this->runGuardFor('index.php');
    }

    public function testViewsIndexExitsBeforeSentinel()
    {
        $this->runGuardFor('views/index.php');
    }

    public function testTemplatesIndexExitsBeforeSentinel()
    {
        $this->runGuardFor('views/templates/index.php');
    }

    public function testHookTemplatesIndexExitsBeforeSentinel()
    {
        $this->runGuardFor('views/templates/hook/index.php');
    }

    public function testUpgradeIndexExitsBeforeSentinel()
    {
        $this->runGuardFor('upgrade/index.php');
    }

    public function testTestsIndexExitsBeforeSentinel()
    {
        $this->runGuardFor('tests/index.php');
    }

    public function testPhpstanIndexExitsBeforeSentinel()
    {
        $this->runGuardFor('tests/phpstan/index.php');
    }

    private function runGuardFor($relative)
    {
        $root = dirname(__DIR__);
        $target = $root . '/' . $relative;
        $wrapper = $this->makeTempWrapper(
            '<?php error_reporting(-1); include ' . var_export($target, true) . '; echo "SENTINEL_REACHED\n";'
        );
        $result = $this->runPhpChild($wrapper);
        $this->assertSame(0, $result['exit']);
        $this->assertSame('', $result['stdout']);
        $this->assertSame('', $result['stderr']);
    }

    private function makeTempWrapper($body)
    {
        $dir = sys_get_temp_dir() . '/ps_sharebuttons_' . uniqid('', true);
        mkdir($dir);
        $path = $dir . '/wrapper.php';
        file_put_contents($path, $body);

        return $path;
    }
}
