<?php

class WidgetVariablesTest extends SharebuttonsTestCase
{
    public function testNullWhenControllerCannotProvideAProduct()
    {
        $this->context->controller = new stdClass();
        $this->assertNull($this->module->getWidgetVariables('displayProductAdditionalInfo', array()));
    }

    public function testNullWhenProductIdIsEmpty()
    {
        $product = new SharebuttonsProductStub();
        $product->id = '';
        $this->setControllerProduct($product);
        $this->assertNull($this->module->getWidgetVariables('hook', array()));
    }

    public function testNullWhenProductIdIsZero()
    {
        $product = new SharebuttonsProductStub();
        $product->id = 0;
        $this->setControllerProduct($product);
        $this->assertNull($this->module->getWidgetVariables('hook', array()));
    }

    public function testNullWhenGetProductReturnsNull()
    {
        $controller = new SharebuttonsControllerStub();
        $controller->product = null;
        $this->context->controller = $controller;
        $this->assertNull($this->module->getWidgetVariables('hook', array()));
    }

    public function testNullWhenGetProductReturnsFalse()
    {
        $controller = new SharebuttonsControllerStub();
        $controller->product = false;
        $this->context->controller = $controller;
        $this->assertNull($this->module->getWidgetVariables('hook', array()));
    }

    public function testEnabledNetworksExposeFacebookTwitterAndPinterest()
    {
        $this->enableAllNetworks();
        $vars = $this->module->getWidgetVariables('hook', array());
        $keys = array_keys($vars['social_share_links']);
        sort($keys);
        $this->assertSame(array('facebook', 'pinterest', 'twitter'), $keys);
        $this->assertSame('facebook', $vars['social_share_links']['facebook']['class']);
        $this->assertSame('twitter', $vars['social_share_links']['twitter']['class']);
        $this->assertSame('pinterest', $vars['social_share_links']['pinterest']['class']);
        $this->assertSame('SENT_SHOP_SHARE', $vars['social_share_links']['facebook']['label']);
        $this->assertSame('SENT_SHOP_TWEET', $vars['social_share_links']['twitter']['label']);
        $this->assertSame('SENT_SHOP_PINTEREST', $vars['social_share_links']['pinterest']['label']);
    }

    public function testFacebookLinkOnly()
    {
        Configuration::updateValue('PS_SC_FACEBOOK', '1');
        Configuration::updateValue('PS_SC_TWITTER', 0);
        Configuration::updateValue('PS_SC_PINTEREST', 0);
        $vars = $this->module->getWidgetVariables('hook', array());
        $this->assertSame(array('facebook'), array_keys($vars['social_share_links']));
    }

    public function testTwitterLinkOnly()
    {
        Configuration::updateValue('PS_SC_FACEBOOK', 0);
        Configuration::updateValue('PS_SC_TWITTER', 1);
        Configuration::updateValue('PS_SC_PINTEREST', 0);
        $vars = $this->module->getWidgetVariables('hook', array());
        $this->assertSame(array('twitter'), array_keys($vars['social_share_links']));
    }

    public function testPinterestLinkOnly()
    {
        Configuration::updateValue('PS_SC_FACEBOOK', 0);
        Configuration::updateValue('PS_SC_TWITTER', 0);
        Configuration::updateValue('PS_SC_PINTEREST', 1);
        $vars = $this->module->getWidgetVariables('hook', array());
        $this->assertSame(array('pinterest'), array_keys($vars['social_share_links']));
    }

    public function testDisabledNetworksReturnAnEmptyList()
    {
        $vars = $this->module->getWidgetVariables('hook', array());
        $this->assertSame(array('social_share_links' => array()), $vars);
    }

    public function testStringZeroIsOff()
    {
        Configuration::updateValue('PS_SC_FACEBOOK', '0');
        Configuration::updateValue('PS_SC_TWITTER', '0');
        Configuration::updateValue('PS_SC_PINTEREST', '0');
        $vars = $this->module->getWidgetVariables('hook', array());
        $this->assertSame(array(), $vars['social_share_links']);
    }

    public function testParsedShareTargetsRoundTripLinkNameAndImage()
    {
        $this->enableAllNetworks();
        $vars = $this->module->getWidgetVariables('hook', array());
        $productUrl = 'https://shop.example/ceramic-mug';
        $nameEnc = urlencode(addcslashes('Ceramic Mug', "'"));
        $urlEnc = urlencode(addcslashes($productUrl, "'"));

        $fb = $this->parseQueryUrl($vars['social_share_links']['facebook']['url']);
        $this->assertSame('https', $fb['parts']['scheme']);
        $this->assertSame('www.facebook.com', $fb['parts']['host']);
        $this->assertArrayHasKey('u', $fb['query']);
        $this->assertSame($productUrl, $fb['query']['u']);

        $twUrl = $vars['social_share_links']['twitter']['url'];
        $this->assertContains('https://twitter.com/intent/tweet?text=', $twUrl);
        $this->assertContains($nameEnc, $twUrl);
        $this->assertContains($urlEnc, $twUrl);

        $pin = $this->parseQueryUrl($vars['social_share_links']['pinterest']['url']);
        $this->assertSame('https', $pin['parts']['scheme']);
        $this->assertSame('www.pinterest.com', $pin['parts']['host']);
        $this->assertArrayHasKey('url', $pin['query']);
        $this->assertArrayHasKey('media', $pin['query']);
        $this->assertArrayHasKey('description', $pin['query']);
        $this->assertSame($productUrl, $pin['query']['url']);
        $this->assertSame('Ceramic Mug', $pin['query']['description']);
        $this->assertSame('https://cdn.example/7/ceramic-mug.jpg', $pin['query']['media']);
        $this->assertNotContains('/&media=', $vars['social_share_links']['pinterest']['url']);
        $this->assertSame(42, $this->product->lastCoverProductId);
        $this->assertSame('ceramic-mug', Link::$lastImageRewrite);
        $this->assertSame('7', Link::$lastImageIdArg);
    }

    public function testCoverImageIdIsPassedAsDecimalString()
    {
        $this->enableAllNetworks();
        $this->product->coverReturn = array('id_image' => '0007');
        $this->module->getWidgetVariables('hook', array());
        $this->assertSame('7', Link::$lastImageIdArg);
    }

    public function testAbsentCoverRequestsImageIdZero()
    {
        $this->enableAllNetworks();
        $this->product->coverReturn = false;
        $this->module->getWidgetVariables('hook', array());
        $this->assertInternalType('string', Link::$lastImageIdArg);
        $this->assertSame('0', Link::$lastImageIdArg);
    }

    public function testCoverArrayWithoutImageIdRequestsImageIdZero()
    {
        $this->enableAllNetworks();
        $this->product->coverReturn = array('cover' => 1);
        $this->module->getWidgetVariables('hook', array());
        $this->assertSame('0', Link::$lastImageIdArg);
    }
}
