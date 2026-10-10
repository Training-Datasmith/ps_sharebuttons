<?php

class SuiteManifestTest extends SharebuttonsTestCase
{
    private static $expectedFiles = array(
        'tests/ConfigFormTest.php',
        'tests/DirectoryGuardTest.php',
        'tests/InstallTest.php',
        'tests/ModuleFileGuardTest.php',
        'tests/ModuleMetadataTest.php',
        'tests/RenderWidgetTest.php',
        'tests/SuiteManifestTest.php',
        'tests/UpgradeTest.php',
        'tests/WidgetVariablesTest.php',
    );

    private static $expectedTestCount = 53;

    public function testPlannedFilesMatchDirectory()
    {
        $root = dirname(__DIR__);
        $found = glob($root . '/tests/*Test.php');
        $relative = array();
        foreach ($found as $path) {
            $relative[] = 'tests/' . basename($path);
        }
        sort($relative);
        $this->assertSame(self::$expectedFiles, $relative);
    }

    public function testPlannedTestMethodCount()
    {
        $count = 0;
        foreach (self::$expectedFiles as $file) {
            $src = file_get_contents(dirname(__DIR__) . '/' . $file);
            $count += preg_match_all('/function\s+test\w+/i', $src);
        }
        $this->assertSame(self::$expectedTestCount, (int) $count);
    }
}
