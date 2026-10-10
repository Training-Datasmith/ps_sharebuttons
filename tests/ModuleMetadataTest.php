<?php

class ModuleMetadataTest extends SharebuttonsTestCase
{
    public function testConstructorIdentity()
    {
        $this->assertSame('ps_sharebuttons', $this->module->name);
        $this->assertSame('advertising_marketing', $this->module->tab);
        $this->assertSame('PrestaShop', $this->module->author);
        $this->assertSame('2.1.3', $this->module->version);
        $this->assertSame(0, $this->module->need_instance);
        $this->assertTrue($this->module->bootstrap);
    }

    public function testVersionCompliancyUsesRuntimeConstant()
    {
        $this->assertSame('1.7.1.0', _PS_VERSION_);
        $this->assertSame('1.7.1.0', $this->module->ps_versions_compliancy['min']);
        $this->assertSame(_PS_VERSION_, $this->module->ps_versions_compliancy['max']);
    }

    public function testTranslatedDisplayNameAndDescriptionUseAdminSentinels()
    {
        $this->assertSame('SENT_ADMIN_DISPLAY', $this->module->displayName);
        $this->assertSame('SENT_ADMIN_DESC', $this->module->description);
    }

    public function testPrivateTemplatePath()
    {
        $ref = new ReflectionClass('Ps_Sharebuttons');
        $prop = $ref->getProperty('templateFile');
        $prop->setAccessible(true);
        $this->assertSame(
            'module:ps_sharebuttons/views/templates/hook/ps_sharebuttons.tpl',
            $prop->getValue($this->module)
        );
        $tpl = dirname(__DIR__) . '/views/templates/hook/ps_sharebuttons.tpl';
        $real = realpath($tpl);
        $this->assertInternalType('string', $real);
        $this->assertNotSame('', $real);
        $this->assertContains('views/templates/hook/ps_sharebuttons.tpl', $real);
    }

    public function testWidgetInterfaceSignature()
    {
        $implements = class_implements('Ps_Sharebuttons');
        $this->assertArrayHasKey('PrestaShop\\PrestaShop\\Core\\Module\\WidgetInterface', $implements);
        $ref = new ReflectionClass('Ps_Sharebuttons');
        $render = $ref->getMethod('renderWidget');
        $this->assertCount(2, $render->getParameters());
        $vars = $ref->getMethod('getWidgetVariables');
        $this->assertCount(2, $vars->getParameters());
        $this->assertTrue($render->getParameters()[1]->isArray());
        $this->assertTrue($vars->getParameters()[1]->isArray());
    }

    public function testLegacyModuleConstant()
    {
        $this->assertSame('socialsharing', Ps_Sharebuttons::PS_16_EQUIVALENT_MODULE);
    }

    public function testConfigXmlNameAndVersionMatchClass()
    {
        $xmlPath = dirname(__DIR__) . '/config.xml';
        $xml = simplexml_load_string(file_get_contents($xmlPath));
        $this->assertInstanceOf('SimpleXMLElement', $xml);
        $this->assertSame('ps_sharebuttons', (string) $xml->name);
        $this->assertSame('2.1.3', (string) $xml->version);
        $this->assertSame($this->module->version, (string) $xml->version);
    }
}
