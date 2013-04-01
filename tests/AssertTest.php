<?php

class AssertTest extends \phpunit_framework_testcase
{
    public static function provider()
    {
        $data = array();
        foreach (glob(__DIR__ . "/assert/*/*") as $file) {
            $data[] = array($file, basename(dirname($file)));
        }
        return $data;
    }

    /**
     *  @dataProvider provider
     */
    public function testAll($file, $expected)
    {
        $detect = new LanguageDetector\Detect(__DIR__."/../example/datafile.php");
        $lang = $detect->detect(file_get_contents($file));
        if (is_array($lang)) {
            $this->assertEquals($expected, $lang[0]['lang']);
            return;
        }
        $this->assertEquals($expected, $lang);
    }
}

