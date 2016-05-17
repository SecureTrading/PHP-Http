<?php

namespace Securetrading\Http\Tests\Unit;

require_once(__DIR__ . '/helpers/CoreMocks.php');

class HelperTest extends \Securetrading\Unittest\UnittestAbstract {
  private $_helper;

  private $_serverBak = array();

  public function setUp() {
    $this->_helper = new \Securetrading\Http\Helper();
    $this->_serverBak = $_SERVER;
  }

  public function tearDown() {
    $_SERVER = $this->_serverBak;
    \Securetrading\Unittest\CoreMocker::releaseCoreMocks();
  }

  public function testRetrieveGetParams() {
    $_SERVER['QUERY_STRING'] = 'key1=value1&key2=value%23%3F2';
    $expectedReturnValue = array(
      'key1' => array('value1'),
      'key2' => array('value#?2'),
    );
    $actualReturnValue = $this->_helper->retrieveGetParams();
    $this->assertEquals($expectedReturnValue, $actualReturnValue);
  }

  public function testRetrievePostParams() {
    $that = $this;
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('file_get_contents', function($file) use ($that) {
      $that->assertEquals('php://input', $file);
      return 'key1=value1&key2=value%23%3F2';
    });

    $expectedReturnValue = array(
      'key1' => array('value1'),
      'key2' => array('value#?2'),
    );
    $actualReturnValue = $this->_helper->retrievePostParams();
    $this->assertEquals($expectedReturnValue, $actualReturnValue);
  }
}