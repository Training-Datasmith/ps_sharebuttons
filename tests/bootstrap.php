<?php

error_reporting(-1);

if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '1.7.1.0');
}

require_once dirname(__DIR__) . '/tests/prestashop-stub.php';
require_once dirname(__DIR__) . '/tests/SharebuttonsTestCase.php';

require_once dirname(__DIR__) . '/ps_sharebuttons.php';
