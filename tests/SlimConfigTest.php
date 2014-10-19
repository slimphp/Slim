<?php
//testing all config options
use Slim\Slim;

class SlimConfigTest extends
    PHPUnit_Framework_TestCase
{

    /**
     * @var Slim
     * @since 1.0
     */
    protected $slim;

    protected function setUp()
    {
        $this->slim = new Slim();
    }

    public function testXmlConfig()
    {
        $file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'test-config.xml';
        $results = $this->slim->configFile($file);
        $errors = libxml_get_errors();
        if(empty($errors)){
        $this->assertTrue($results);
        $test_key = $this->slim->config('test_key');
        $test_bool = $this->slim->config('test_bool');
        $this->assertEquals('test_value', $test_key);
        $this->assertTrue($test_bool);
        } else{
            print_r($errors);
        }
    }

    public function testJsonConfig()
    {
        $file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'test-config.json';
        $results = $this->slim->configFile($file);
        $this->assertTrue($results);
        $test_key = $this->slim->config('test_key');
        $test_bool = $this->slim->config('test_bool');
        $this->assertEquals('test_value', $test_key);
        $this->assertTrue($test_bool);
    }

    public function testPhpConfig()
    {
        $file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'test-config.php';
        $results = $this->slim->configFile($file);
        $this->assertTrue($results);
        $test_key = $this->slim->config('test_key');
        $test_bool = $this->slim->config('test_bool');
        $this->assertEquals('test_value', $test_key);
        $this->assertTrue($test_bool);
    }

    public function testIniConfig()
    {
        $file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'test-config.ini';
        $results = $this->slim->configFile($file);
        $this->assertTrue($results);
        $test_key = $this->slim->config('test_key');
        $test_bool = $this->slim->config('test_bool');
        $this->assertEquals('test_value', $test_key);
        $this->assertTrue($test_bool);
    }
}
 
