<?php

namespace Securetrading\Http\Tests\Unit;

require_once(__DIR__ . '/helpers/CoreMocks.php');

class CurlTest extends \Securetrading\Unittest\UnittestAbstract {
  public function setUp() {
    $this->_curl = new \Securetrading\Http\Curl($this->getMockForAbstractClass('\Psr\Log\LoggerInterface'));
    \Securetrading\Unittest\CoreMocker::releaseCoreMocks();
  }

  private function _mockCurlSetAndExec(&$calls) {
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_exec', 'curl_exec_rv');
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_setopt', function($ch, $k, $v) use (&$calls) {
      if (!array_key_exists($k, $calls)) {
	$calls[$k] = array(
	  'count' => 1,
	  'values' => array($v),
	);
      }
      else {
	$calls[$k]['count'] += 1;
	$calls[$k]['values'][] = $v;
      }
    });
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_setopt_array', function($ch, array $curlData) use (&$calls) {
      foreach($curlData as $k => $v) {
	if (!array_key_exists($k, $calls)) {
	  $calls[$k] = array(
	    'count' => 1,
	    'values' => array($v),
	  );
	}
	else {
	  $calls[$k]['count'] += 1;
	  $calls[$k]['values'][] = $v;
	}
      }
    });
    return $calls;
  }

  private function _testingCurlSetoptWrapper(array $data, $functionBody) {
    $functionsToCall = $data[0];
    $expectedCurlKeys = array_slice($data, 1);

    $calls = array();
    $this->_mockCurlSetAndExec($calls);

    foreach($functionsToCall as $function => $params) {
      call_user_func_array(array($this->_curl, $function), $params);
    }
    
    $functionBody();
    
    foreach($expectedCurlKeys as $expectedCurl) {
      $expectedKey = $expectedCurl[0];
      $expectedCount = $expectedCurl[1];
      $expectedValues = isset($expectedCurl[2]) ? $expectedCurl[2] : array();
      if ($expectedCount === 0) {
	$this->assertTrue(!isset($calls[$expectedKey]));
      }
      else {
	$this->assertEquals($expectedCount, $calls[$expectedKey]['count']);
	if (is_callable($expectedValues)) {
	  $this->assertEquals(true, $expectedValues($calls[$expectedKey]['values']));
	}
	else {
	  $this->assertEquals($expectedValues, $calls[$expectedKey]['values']);
	}
      }
    }
  }

  /**
   *
   */
  public function testSetUsername() {
    $returnValue = $this->_curl->setUsername('username');
    $this->assertSame($this->_curl, $returnValue);
  }

  /**
   *
   */
  public function testSetPassword() {
    $returnValue = $this->_curl->setPassword('password');
    $this->assertSame($this->_curl, $returnValue);
  }

  /**
   *
   */
  public function testSetUserAgent() {
    $returnValue = $this->_curl->setUserAgent('Secure Trading cURL.');
    $this->assertSame($this->_curl, $returnValue);
  }

  /**
   *
   */
  public function testSetProxyHost() {
    $returnValue = $this->_curl->setProxyHost('127.0.0.2/');
    $this->assertSame($this->_curl, $returnValue);
  }

  /**
   *
   */
  public function testSetProxyPort() {
    $returnValue = $this->_curl->setProxyPort('443');
    $this->assertSame($this->_curl, $returnValue);
  }

  /**
   *
   */
  public function testSetSslVerifyPeer() {
    $returnValue = $this->_curl->setSslVerifyPeer(true);
    $this->assertSame($this->_curl, $returnValue);
  }

  /**
   *
   */
  public function testSetSslVerifyHost() {
    $returnValue = $this->_curl->setSslVerifyHost(2);
    $this->assertSame($this->_curl, $returnValue);
  }

  /**
   *
   */
  public function testSetSslCaCertFile() {
    $returnValue = $this->_curl->setSslCaCertFile('/tmp/ca_file.pem');
    $this->assertSame($this->_curl, $returnValue);
  }
  
  /**
   *
   */
  public function testSetConnectTimeout() {
    $returnValue = $this->_curl->setConnectTimeout(10);
    $this->assertSame($this->_curl, $returnValue);
  }

  /**
   *
   */
  public function testSetConnectAttemptsTimeout() {
    $returnValue = $this->_curl->setConnectAttemptsTimeout(20);
    $this->assertSame($this->_curl, $returnValue);
  }

  /**
   *
   */
  public function testSetTimeout() {
    $returnValue = $this->_curl->setTimeout(60);
    $this->assertSame($this->_curl, $returnValue);
  }

  /**
   *
   */
  public function testSetConnectAttempts() {
    $returnValue = $this->_curl->setConnectAttempts(5);
    $this->assertSame($this->_curl, $returnValue);
  }

  /**
   *
   */
  public function testSetSleepUseconds() {
    $returnValue = $this->_curl->setSleepUseconds(2000000);
    $this->assertSame($this->_curl, $returnValue);
  }

  /**
   *
   */
  public function testSetCurlOptions() {
    $returnValue = $this->_curl->setCurlOptions(array(CURLOPT_POSTFIELDS => ''));
    $this->assertSame($this->_curl, $returnValue);
  }

  /**
   *
   */
  public function testSetCurlOption() {
    $returnValue = $this->_curl->setCurlOption(CURLOPT_POSTFIELDS, '');
    $this->assertSame($this->_curl, $returnValue);
  }

  /**
   *
   */
  public function testSetUrl() {
    $returnValue = $this->_curl->setUrl('http://127.0.0.1/');
    $this->assertSame($this->_curl, $returnValue);
  }

  /**
   *
   */
  public function testGetUrl() {
    $this->assertEquals('', $this->_curl->getUrl());
    $this->_curl->setUrl('http://www.securetrading.com');
    $this->assertEquals('http://www.securetrading.com', $this->_curl->getUrl());
  }

  /**
   *
   */
  public function testSetRequestHeaders() {
    $returnValue = $this->_curl->setRequestHeaders(array('User-Agent: test', 'Content-Type: text/xml'));
    $this->assertSame($this->_curl, $returnValue);
  }

  /**
   *
   */
  public function testaddRequestHeader() {
    $returnValue = $this->_curl->addRequestHeader('User-Agent: test');
    $this->assertSame($this->_curl, $returnValue);
  }

  /**
   *
   */
  public function testGetRequestHeaders() {
    $this->assertEquals(array(), $this->_curl->getRequestHeaders());
    $this->_curl->addRequestHeader('Content-Type: text/xml');
    $this->assertEquals(array('Content-Type: text/xml'), $this->_curl->getRequestHeaders());
    $this->_curl->addRequestHeader('User-Agent: test');
    $this->assertEquals(array('Content-Type: text/xml', 'User-Agent: test'), $this->_curl->getRequestHeaders());
    $this->_curl->setRequestHeaders(array('Content-Length: 1234', 'Transfer-Encoding: chunked'));
    $this->assertEquals(array('Content-Length: 1234', 'Transfer-Encoding: chunked'), $this->_curl->getRequestHeaders());
  }

  /**
   * 
   */
  public function testGet() {
    $that = $this;
    $this->_testingCurlSetoptWrapper(array(array()), function() use ($that) {
      $returnValue = $that->_curl->get();
      $that->assertEquals('curl_exec_rv', $returnValue);
    });
  }

  /**
   * @dataProvider providerSend
   */
  public function testSend() {
    $args = func_get_args();
    $requestMethod = array_shift($args);
    $requestBody = array_shift($args);
    $that = $this;
    $this->_testingCurlSetoptWrapper($args, function() use ($that, $requestMethod, $requestBody) {
      if ($requestBody) {
	$returnValue = $that->_curl->send($requestMethod, $requestBody);
      }
      else {
	$returnValue = $that->_curl->send($requestMethod);
      }
      $that->assertEquals('curl_exec_rv', $returnValue);
    });
  }

  public function providerSend() {
    $this->_addDataSet(
      'PATCH',
      'request_body',
      array(),
      array(CURLOPT_CUSTOMREQUEST, 1, array('PATCH')),
      array(CURLOPT_POSTFIELDS, 1, array('request_body'))
    );
    $this->_addDataSet(
      'PATCH',
      null,
      array(),
      array(CURLOPT_CUSTOMREQUEST, 1, array('PATCH')),
      array(CURLOPT_POSTFIELDS, 0)
    );
    return $this->_getDataSets();
  }

  /**
   * @dataProvider providerPost
   */
  public function testPost() {
    $args = func_get_args();
    $postArg = array_shift($args);
    $that = $this;
    $this->_testingCurlSetoptWrapper($args, function() use ($that, $postArg) {
      if ($postArg) {
	$returnValue = $that->_curl->post($postArg);
      }
      else {
	$returnValue = $that->_curl->post();
      }
      $that->assertEquals('curl_exec_rv', $returnValue);
    });
  }

  public function providerPost() {
    $this->_addDataSet(
      'request_body',
      array(),
      array(CURLOPT_POST, 1, array(1)),
      array(CURLOPT_POSTFIELDS, 1, array('request_body'))
    );
    $this->_addDataSet(
      null,
      array(),
      array(CURLOPT_POST, 1, array(1)),
      array(CURLOPT_POSTFIELDS, 1, array(''))
    );
    return $this->_getDataSets();
  }

  /**
   * @dataProvider provider_prepareCurl
   */
  public function test_prepareCurl() {
    $that = $this;
    $this->_testingCurlSetoptWrapper(func_get_args(), function() use ($that) {
      $that->_curl->post('request_body');
    });
  }

  public function provider_prepareCurl() {
    $this->_addDataSet(
      array(),
      array(CURLOPT_FOLLOWLOCATION, 1, array(true))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_URL, 1, array(''))
    );
    $this->_addDataSet(
      array('setUrl' => array('http://www.securetrading.com')),
      array(CURLOPT_URL, 1, array('http://www.securetrading.com'))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_USERAGENT, 1, array(''))
    );
    $this->_addDataSet(
      array('setUserAgent' => array('user_agent')),
      array(CURLOPT_USERAGENT, 1, array('user_agent'))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_SSL_VERIFYPEER, 1, array(2))
    );
    $this->_addDataSet(
      array('setSslVerifyPeer' => array(false)),
      array(CURLOPT_SSL_VERIFYPEER, 1, array(false))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_SSL_VERIFYHOST, 1, array(true))
    );
    $this->_addDataSet(
      array('setSslVerifyHost' => array(false)),
      array(CURLOPT_SSL_VERIFYHOST, 1, array(false))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_CONNECTTIMEOUT, 1, array(5))
    );
    $this->_addDataSet(
      array('setConnectTimeout' => array(10)),
      array(CURLOPT_CONNECTTIMEOUT, 1, array(10))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_TIMEOUT, 1, array(60))
    );
    $this->_addDataSet(
      array('setTimeout' => array(30)),
      array(CURLOPT_TIMEOUT, 1, array(30))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_HTTPHEADER, 1, array(array()))
    );
    $this->_addDataSet(
      array('setRequestHeaders' => array(array('Content-Type: text/xml')), 'addRequestHeader' => array('Origin: http://www.securetrading.com')),
      array(CURLOPT_HTTPHEADER, 1, array(array('Content-Type: text/xml', 'Origin: http://www.securetrading.com')))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_VERBOSE, 1, array(true))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_STDERR, 1, function(array $values) { return is_resource($values[0]); })
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_CAINFO, 0)
    );
    $this->_addDataSet(
      array('setSslCaCertFile' => array('')),
      array(CURLOPT_CAINFO, 0)
    );
    $this->_addDataSet(
      array('setSslCaCertFile' => array('/tmp/cert.pem')),
      array(CURLOPT_CAINFO, 1, array('/tmp/cert.pem'))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_PROXY, 0)
    );
    $this->_addDataSet(
      array('setProxyHost' => array('')),
      array(CURLOPT_PROXY, 0)
    );
    $this->_addDataSet(
      array('setProxyHost' => array('http://www.securetrading.com')),
      array(CURLOPT_PROXY, 1, array('http://www.securetrading.com'))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_PROXYPORT, 0)
    );
    $this->_addDataSet(
      array('setProxyPort' => array('')),
      array(CURLOPT_PROXYPORT, 0)
    );
    $this->_addDataSet(
      array('setProxyPort' => array('8080')),
      array(CURLOPT_PROXYPORT, 1, array('8080'))
    );
    $this->_addDataSet(
      array('setUsername' => array('user@securetrading.com')),
      array(CURLOPT_HTTPAUTH, 0),
      array(CURLOPT_USERPWD, 0)
    );
    $this->_addDataSet(
      array('setPassword' => array('password')),
      array(CURLOPT_HTTPAUTH, 0),
      array(CURLOPT_USERPWD, 0)
    );
    $this->_addDataSet(
      array('setUsername' => array('user@securetrading.com'), 'setPassword' => array('password')),
      array(CURLOPT_HTTPAUTH, 1, array(CURLAUTH_BASIC)),
      array(CURLOPT_USERPWD, 1, array('user@securetrading.com:password'))
    );

    $this->_addDataSet(
      array('setCurlOptions' => array(array(CURLOPT_VERBOSE => false, CURLOPT_URL => "http://www.test.com")), 'setCurlOption' => array(CURLOPT_CRLF, true)),
      array(CURLOPT_VERBOSE, 2, array(true, false)),
      array(CURLOPT_URL, 2, array("", "http://www.test.com")),
      array(CURLOPT_CRLF, 1, array(true))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_RETURNTRANSFER, 1, array(true))
    );
    return $this->_getDataSets();
  }

  /**
   *
   */
  public function testGetInfo() {
    $that = $this;
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_getinfo', function($ch, $curlinfoConstant) use ($that) {
      $that->assertEquals(CURLINFO_HTTP_CODE, $curlinfoConstant);
      return 'returned_value';
    });
    $returnValue = $this->_curl->getInfo(CURLINFO_HTTP_CODE);
    $this->assertEquals('returned_value', $returnValue);
  }

  /**
   *
   */
  public function testGetResponseCode() {
    $that = $this;
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_getinfo', function($ch, $curlinfoConstant) use ($that) {
      $that->assertEquals(CURLINFO_HTTP_CODE, $curlinfoConstant);
      return 'returned_value';
    });
    $returnValue = $this->_curl->getResponseCode();
    $this->assertEquals('returned_value', $returnValue);
  }

  // Note - getLogData(), _sendAndReceive(), _sendAndReceiveWithRetries() not unit tested.

  /**
   *
   */
  public function test_exec() {
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_exec', true);
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_errno', 0);
    $returnValue = $this->_($this->_curl, '_exec');
    $this->assertEquals(array(true, 0), $returnValue);
  }
  
  /**
   * @dataProvider provider_canRetry
   */
  public function test_canRetry($startTime, $i, $expectedReturnValue) {
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('time', 40);
    $this->_curl->setConnectTimeout(5);
    $this->_curl->setSleepUseconds(1000000);
    $this->_curl->setConnectAttemptsTimeout(26);
    $this->_curl->setConnectAttempts(10);
    $actualReturnValue = $this->_($this->_curl, '_canRetry', $startTime, $i);
    $this->assertEquals($expectedReturnValue, $actualReturnValue);
  }

  public function provider_canRetry() {
    $this->_addDataSet(21, 0, true);
    $this->_addDataSet(20, 0, true);
    $this->_addDataSet(19, 0, false);
    $this->_addDataSet(21, 9, true);
    $this->_addDataSet(21, 10, false);
    return $this->_getDataSets();
  }

  /**
   *
   */
  public function test_microsecondsToSeconds() {
    $this->assertEquals(6, $this->_($this->_curl, '_microsecondsToSeconds', 6000000));
  }

  /**
   * @expectedException \Securetrading\Http\CurlException
   * @expectedExceptionCode \Securetrading\Http\CurlException::CODE_BAD_RESULT
   */
  public function test_checkResult_WhenError() {
    $this->_($this->_curl, '_checkResult', false);
  }

  /**
   *
   */
  public function test_checkResult_WhenNoError() {
    $returnValue = $this->_($this->_curl, '_checkResult', true);
    $this->assertSame($this->_curl, $returnValue);
  }
}