<?php

class ModuleFileGuardTest extends SharebuttonsTestCase
{
    public function testSharebuttonsPhpExitsWhenVersionConstantMissing()
    {
        $root = dirname(__DIR__);
        $target = $root . '/ps_sharebuttons.php';
        $wrapper = $this->makeTempWrapper(
            '<?php error_reporting(-1); include ' . var_export($target, true) . '; echo "SENTINEL_REACHED\n";'
        );
        $result = $this->runPhpChild($wrapper);
        $this->assertSame(0, $result['exit']);
        $this->assertSame('', $result['stdout']);
        $this->assertSame('', $result['stderr']);
    }

    public function testUpgradeScriptExitsWhenVersionConstantMissing()
    {
        $root = dirname(__DIR__);
        $target = $root . '/upgrade/install-2.1.2.php';
        $wrapper = $this->makeTempWrapper(
            '<?php error_reporting(-1); include ' . var_export($target, true) . '; echo "SENTINEL_REACHED\n";'
        );
        $result = $this->runPhpChild($wrapper);
        $this->assertSame(0, $result['exit']);
        $this->assertSame('', $result['stdout']);
        $this->assertSame('', $result['stderr']);
    }

    public function testSharebuttonsPhpDefinesClassWhenVersionConstantPresent()
    {
        $root = dirname(__DIR__);
        $stub = $root . '/tests/prestashop-stub.php';
        $module = $root . '/ps_sharebuttons.php';
        $wrapper = $this->makeTempWrapper(
            '<?php error_reporting(-1); define("_PS_VERSION_", "1.7.1.0"); require_once '
            . var_export($stub, true) . '; require_once ' . var_export($module, true) . ';'
            . '$m = new Ps_Sharebuttons(); echo get_class($m), "\n", $m->version, "\n";'
        );
        $result = $this->runPhpChild($wrapper);
        $this->assertSame(0, $result['exit']);
        $this->assertSame("Ps_Sharebuttons\n2.1.3\n", $result['stdout']);
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
