<?php

class InstallTest extends SharebuttonsTestCase
{
    public function testFreshInstallEnablesThreeNetworksAndRegistersProductHook()
    {
        Module::$isInstalledMap = array('socialsharing' => false);
        $this->assertTrue($this->module->install());
        $this->assertSame(1, Configuration::get('PS_SC_TWITTER'));
        $this->assertSame(1, Configuration::get('PS_SC_FACEBOOK'));
        $this->assertSame(1, Configuration::get('PS_SC_PINTEREST'));
        $this->assertContains('displayProductAdditionalInfo', Module::$registerHookLog);
        $this->assertCount(1, Module::$registerHookLog);
    }

    public function testInstallKeepsExistingNetworkFlagsWhenLegacyModuleIsRemoved()
    {
        Module::$isInstalledMap = array('socialsharing' => true);
        Module::$getInstanceByNameMap = array('socialsharing' => new Socialsharing());
        Configuration::updateValue('PS_SC_FACEBOOK', 0);
        Configuration::updateValue('PS_SC_TWITTER', 1);
        Configuration::updateValue('PS_SC_PINTEREST', 0);
        $this->assertTrue($this->module->install());
        $this->assertSame(0, Configuration::get('PS_SC_FACEBOOK'));
        $this->assertSame(1, Configuration::get('PS_SC_TWITTER'));
        $this->assertSame(0, Configuration::get('PS_SC_PINTEREST'));
    }

    public function testInstallAbortsBeforeHookRegistrationWhenParentInstallFails()
    {
        Module::$isInstalledMap = array('socialsharing' => false);
        Module::$installReturn = false;
        $this->assertFalse($this->module->install());
        $this->assertSame(array(), Module::$registerHookLog);
    }

    public function testInstallReturnsFalseWhenHookRegistrationFails()
    {
        Module::$isInstalledMap = array('socialsharing' => false);
        Module::$registerHookReturn = false;
        $this->assertFalse($this->module->install());
        $this->assertContains('displayProductAdditionalInfo', Module::$registerHookLog);
    }

    public function testLegacyRemovalInvokesModuleParentUninstallOnTheOldInstance()
    {
        $legacy = new Socialsharing();
        Module::$isInstalledMap = array('socialsharing' => true);
        Module::$getInstanceByNameMap = array('socialsharing' => $legacy);
        Configuration::updateValue('PS_SC_FACEBOOK', 0);
        $this->assertTrue($this->module->uninstallPrestaShop16Module());
        $this->assertSame(1, count(Module::$parentUninstallTargets));
        $this->assertSame($legacy, Module::$parentUninstallTargets[0]);
        $this->assertFalse(Socialsharing::$subclassUninstallCalled);
        $this->assertSame(0, Configuration::get('PS_SC_FACEBOOK'));
    }

    public function testLegacyRemovalReturnsFalseWhenSocialsharingIsNotInstalled()
    {
        Module::$isInstalledMap = array('socialsharing' => false);
        $this->assertFalse($this->module->uninstallPrestaShop16Module());
        $this->assertSame(array(), Module::$parentUninstallTargets);
    }
}
