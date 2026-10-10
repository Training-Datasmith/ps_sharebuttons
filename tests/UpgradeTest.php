<?php

require_once dirname(__DIR__) . '/upgrade/install-2.1.2.php';

class UpgradeTest extends SharebuttonsTestCase
{
    public function testUpgradeReplacesProductButtonsHookWithAdditionalInfo()
    {
        Module::$unregisterHookReturn = true;
        Module::$registerHookReturn = true;
        $this->assertTrue(upgrade_module_2_1_2($this->module));
        $this->assertContains('displayProductButtons', Module::$unregisterHookLog);
        $this->assertContains('displayProductAdditionalInfo', Module::$registerHookLog);
    }

    public function testUpgradeStopsWhenUnregisterFails()
    {
        Module::$unregisterHookReturn = false;
        Module::$registerHookReturn = true;
        $this->assertFalse(upgrade_module_2_1_2($this->module));
        $this->assertSame(array(), Module::$registerHookLog);
    }

    public function testUpgradeReturnsFalseWhenRegisterFails()
    {
        Module::$unregisterHookReturn = true;
        Module::$registerHookReturn = false;
        $this->assertFalse(upgrade_module_2_1_2($this->module));
        $this->assertContains('displayProductButtons', Module::$unregisterHookLog);
        $this->assertContains('displayProductAdditionalInfo', Module::$registerHookLog);
    }
}
