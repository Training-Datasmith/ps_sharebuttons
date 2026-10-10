<?php

class Ps_SharebuttonsRenderProbe extends Ps_Sharebuttons
{
    /** @var array */
    public $probeVariables = array('probe' => 'WIDGET_PROBE');

    public function getWidgetVariables($hookName, array $params)
    {
        return $this->probeVariables;
    }
}

class RenderWidgetTest extends SharebuttonsTestCase
{
    public function testAssignsProbeVariablesAndRecordsTemplatePath()
    {
        $module = new Ps_SharebuttonsRenderProbe();
        $module->probeVariables = array('social_share_links' => array('x' => 1));
        $out = $module->renderWidget('displayProductAdditionalInfo', array());
        $this->assertSame($module->probeVariables, $this->context->smarty->lastAssign);
        $this->assertSame('FETCH_SENTINEL', $out);
        $this->assertContains(
            'module:ps_sharebuttons/views/templates/hook/ps_sharebuttons.tpl',
            Module::$fetchLog
        );
    }

    public function testAssignsEmptyLinkListAndStillFetchesTemplate()
    {
        $module = new Ps_SharebuttonsRenderProbe();
        $module->probeVariables = array('social_share_links' => array());
        $module->renderWidget('hook', array());
        $this->assertSame(array('social_share_links' => array()), $this->context->smarty->lastAssign);
        $this->assertCount(1, Module::$fetchLog);
    }

    public function testHookTemplatePrintsClassHrefAndLabel()
    {
        $path = dirname(__DIR__) . '/views/templates/hook/ps_sharebuttons.tpl';
        $contents = file_get_contents($path);
        $this->assertInternalType('string', $contents);
        $this->assertNotSame('', $contents);
        $this->assertContains("social_share_link.class", $contents);
        $this->assertContains("social_share_link.url", $contents);
        $this->assertContains("social_share_link.label", $contents);
        $this->assertContains('social_share_links', $contents);
        $this->assertContains('social-sharing', $contents);
    }
}
