<?php

class ConfigFormTest extends SharebuttonsTestCase
{
    public function testConfigValuesFallBackToStoredIntegers()
    {
        Configuration::updateValue('PS_SC_FACEBOOK', 1);
        Configuration::updateValue('PS_SC_TWITTER', 0);
        Configuration::updateValue('PS_SC_PINTEREST', 1);
        $values = $this->module->getConfigFieldsValues();
        $this->assertSame(1, $values['PS_SC_FACEBOOK']);
        $this->assertSame(0, $values['PS_SC_TWITTER']);
        $this->assertSame(1, $values['PS_SC_PINTEREST']);
    }

    public function testMissingConfigurationReadsAsIntegerZero()
    {
        $values = $this->module->getConfigFieldsValues();
        $this->assertCount(3, $values);
        $this->assertSame(0, $values['PS_SC_FACEBOOK']);
        $this->assertSame(0, $values['PS_SC_TWITTER']);
        $this->assertSame(0, $values['PS_SC_PINTEREST']);
    }

    public function testPostedValuesOverrideStoredConfiguration()
    {
        Configuration::updateValue('PS_SC_FACEBOOK', 1);
        Configuration::updateValue('PS_SC_TWITTER', 1);
        Configuration::updateValue('PS_SC_PINTEREST', 1);
        $_POST['PS_SC_TWITTER'] = '0';
        $_GET['PS_SC_TWITTER'] = '1';
        $values = $this->module->getConfigFieldsValues();
        $this->assertSame(1, $values['PS_SC_FACEBOOK']);
        $this->assertSame(0, $values['PS_SC_TWITTER']);
        $this->assertSame(1, $values['PS_SC_PINTEREST']);
    }

    public function testNonNumericPostedValueCastsToIntegerZero()
    {
        Configuration::updateValue('PS_SC_FACEBOOK', 1);
        $_POST['PS_SC_FACEBOOK'] = 'abc';
        $values = $this->module->getConfigFieldsValues();
        $this->assertSame(0, $values['PS_SC_FACEBOOK']);
    }

    public function testConfigurationScreenBuildsThreeSwitches()
    {
        $html = $this->module->getContent();
        $this->assertContains('<form-stub/>', $html);
        $this->assertSame('submitSocialSharing', HelperForm::$lastSubmitAction);
        $this->assertSame('lite-token-AdminModules', HelperForm::$lastToken);
        $this->assertContains('configure=ps_sharebuttons', HelperForm::$lastCurrentIndex);
        $this->assertNotContains('token=', HelperForm::$lastCurrentIndex);
        $this->assertSame($this->module->getConfigFieldsValues(), HelperForm::$lastTplVars['fields_value']);
        $this->assertNotNull(HelperForm::$lastGenerateFormArg);
        $form = HelperForm::$lastGenerateFormArg;
        $input = $form[0]['form']['input'];
        $names = array();
        foreach ($input as $field) {
            $names[$field['name']] = $field;
        }
        $this->assertArrayHasKey('PS_SC_FACEBOOK', $names);
        $this->assertArrayHasKey('PS_SC_TWITTER', $names);
        $this->assertArrayHasKey('PS_SC_PINTEREST', $names);
        $this->assertSame('switch', $names['PS_SC_FACEBOOK']['type']);
        $this->assertSame('Facebook', $names['PS_SC_FACEBOOK']['label']);
        $legend = $form[0]['form']['legend'];
        $this->assertSame($this->module->displayName, $legend['title']);
        $this->assertSame('icon-share', $legend['icon']);
        $submitTitle = $form[0]['form']['submit']['title'];
        $this->assertTranslationTokenDomain($submitTitle, 'Admin.Actions');
        $labels = array();
        foreach ($names['PS_SC_FACEBOOK']['values'] as $choice) {
            $labels[] = $choice['label'];
        }
        $this->assertCount(2, $labels);
        $this->assertTranslationTokenDomain($labels[0], 'Admin.Global');
        $this->assertTranslationTokenDomain($labels[1], 'Admin.Global');
        $this->assertNotSame($labels[0], $labels[1]);
    }

    public function testSavingWritesIntegersClearsTemplateCacheAndRedirects()
    {
        $_POST['submitSocialSharing'] = '1';
        $_POST['PS_SC_FACEBOOK'] = '1';
        $_POST['PS_SC_TWITTER'] = '0';
        $_POST['PS_SC_PINTEREST'] = '1';
        try {
            $this->module->getContent();
            $this->fail('Expected redirect');
        } catch (SharebuttonsAdminRedirectException $e) {
            $url = $e->url;
        }
        $this->assertSame(1, Configuration::get('PS_SC_FACEBOOK'));
        $this->assertSame(0, Configuration::get('PS_SC_TWITTER'));
        $this->assertSame(1, Configuration::get('PS_SC_PINTEREST'));
        $this->assertContains('module:ps_sharebuttons/views/templates/hook/ps_sharebuttons.tpl', Module::$clearCacheLog);
        $this->assertTranslationTokenDomain(Module::$lastConfirmation, 'Admin.Notifications.Success');
        $this->assertContains('controller=AdminModules', $url);
        $this->assertContains('token=with-token', $url);
        $this->assertContains('conf=6', $url);
        $this->assertContains('configure=ps_sharebuttons', $url);
    }

    public function testSavingWithoutPostedKeysStoresIntegerZero()
    {
        $_POST['submitSocialSharing'] = '1';
        try {
            $this->module->getContent();
            $this->fail('Expected redirect');
        } catch (SharebuttonsAdminRedirectException $e) {
        }
        $this->assertSame(0, Configuration::get('PS_SC_FACEBOOK'));
        $this->assertSame(0, Configuration::get('PS_SC_TWITTER'));
        $this->assertSame(0, Configuration::get('PS_SC_PINTEREST'));
    }
}
