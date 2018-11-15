<?php

namespace Securetrading\Http\Tests\Unit;

require_once(__DIR__ . '/helpers/CoreMocks.php');

class CurlTest extends \Securetrading\Unittest\UnittestAbstract {
  public function setUp() {
    $this->_logMock = $this->getMockForAbstractClass('\Psr\Log\LoggerInterface');
  }

  public function tearDown() {
    \Securetrading\Unittest\CoreMocker::releaseCoreMocks();
  }

  public function _newInstance(array $configData = array()) { // Note - public so can be called from a closure.
    return new \Securetrading\Http\Curl($this->_logMock, $configData);
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

  private function _testingCurlSetoptWrapper(array $expectedCurlKeys, $functionBody) {
    $calls = array();
    $this->_mockCurlSetAndExec($calls);
    
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
   * @dataProvider providerSend
   */
  public function testSend() {
    $args = func_get_args();
    $requestMethod = array_shift($args);
    $requestBody = array_shift($args);
    $configData = array_shift($args);
    $that = $this;

    $this->_testingCurlSetoptWrapper($args, function() use ($that, $requestMethod, $requestBody) {
      if ($requestBody) {
	$returnValue = $that->_newInstance()->send($requestMethod, $requestBody);
      }
      else {
	$returnValue = $that->_newInstance()->send($requestMethod);
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
   * 
   */
  public function testGet() {
    $that = $this;
    $this->_testingCurlSetoptWrapper(array(), function() use ($that) {
      $returnValue = $that->_newInstance()->get();
      $that->assertEquals('curl_exec_rv', $returnValue);
    });
  }

  /**
   * @dataProvider providerPost
   */
  public function testPost() {
    $args = func_get_args();
    $postArg = array_shift($args);
    $configData = array_shift($args);
    $that = $this;
    $this->_testingCurlSetoptWrapper($args, function() use ($that, $postArg) {
      if ($postArg) {
	$returnValue = $that->_newInstance()->post($postArg);
      }
      else {
	$returnValue = $that->_newInstance()->post();
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
    $args = func_get_args();
    $configData = array_shift($args);

    $that = $this;
    $this->_testingCurlSetoptWrapper($args, function() use ($that, $configData) {
      $that->_newInstance($configData)->post('request_body');
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
      array('url' => 'http://www.securetrading.com'),
      array(CURLOPT_URL, 1, array('http://www.securetrading.com'))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_USERAGENT, 1, array(''))
    );
    $this->_addDataSet(
      array('user_agent' => 'our_user_agent'),
      array(CURLOPT_USERAGENT, 1, array('our_user_agent'))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_SSL_VERIFYPEER, 1, array(2))
    );
    $this->_addDataSet(
      array('ssl_verify_peer' => false),
      array(CURLOPT_SSL_VERIFYPEER, 1, array(false))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_SSL_VERIFYHOST, 1, array(true))
    );
    $this->_addDataSet(
      array('ssl_verify_host' => 0),
      array(CURLOPT_SSL_VERIFYHOST, 1, array(0))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_CONNECTTIMEOUT, 1, array(10))
    );
    $this->_addDataSet(
      array('connect_timeout' => 10),
      array(CURLOPT_CONNECTTIMEOUT, 1, array(10))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_TIMEOUT, 1, array(60))
    );
    $this->_addDataSet(
      array('timeout' => 30),
      array(CURLOPT_TIMEOUT, 1, array(30))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_HTTPHEADER, 1, array(array()))
    );
    $this->_addDataSet(
      array('http_headers' => array('Content-Type: text/xml', 'Origin: http://www.securetrading.com')),
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
      array('ssl_cacertfile' => ''),
      array(CURLOPT_CAINFO, 0)
    );
    $this->_addDataSet(
      array('ssl_cacertfile' => '/tmp/cert.pem'),
      array(CURLOPT_CAINFO, 1, array('/tmp/cert.pem'))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_PROXY, 0)
    );
    $this->_addDataSet(
      array('proxy_host' => ''),
      array(CURLOPT_PROXY, 0)
    );
    $this->_addDataSet(
      array('proxy_host' => 'http://www.securetrading.com'),
      array(CURLOPT_PROXY, 1, array('http://www.securetrading.com'))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_PROXYPORT, 0)
    );
    $this->_addDataSet(
      array('proxy_port' => ''),
      array(CURLOPT_PROXYPORT, 0)
    );
    $this->_addDataSet(
      array('proxy_port' => '8080'),
      array(CURLOPT_PROXYPORT, 1, array('8080'))
    );

    $this->_addDataSet(
      array('username' => 'user@securetrading.com'),
      array(CURLOPT_HTTPAUTH, 0),
      array(CURLOPT_USERPWD, 0)
    );
    $this->_addDataSet(
      array('password' => 'password'),
      array(CURLOPT_HTTPAUTH, 0),
      array(CURLOPT_USERPWD, 0)
    );
    $this->_addDataSet(
      array('username' => 'user@securetrading.com', 'password' => 'password'),
      array(CURLOPT_HTTPAUTH, 1, array(CURLAUTH_BASIC)),
      array(CURLOPT_USERPWD, 1, array('user@securetrading.com:password'))
    );
    $this->_addDataSet(
      array('curl_options' => array(CURLOPT_VERBOSE => false, CURLOPT_URL => 'http://www.test.com', CURLOPT_CRLF => true)),
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
  public function testGetResponseCode() {
    $that = $this;
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_getinfo', function($ch, $curlinfoConstant) use ($that) {
      $that->assertEquals(CURLINFO_HTTP_CODE, $curlinfoConstant);
      return 'returned_value';
    });
    $returnValue = $this->_newInstance()->getResponseCode();
    $this->assertEquals('returned_value', $returnValue);
  }

  // Note - getLogData() not unit tested.

  /**
   *
   */
  public function testGetInfo() {
    $that = $this;
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_getinfo', function($ch, $curlinfoConstant) use ($that) {
      $that->assertEquals(CURLINFO_HTTP_CODE, $curlinfoConstant);
      return 'returned_value';
    });
    $returnValue = $this->_newInstance()->getInfo(CURLINFO_HTTP_CODE);
    $this->assertEquals('returned_value', $returnValue);
  }

  public function testUpdateConfig() {
    $curl = $this->_newInstance();

    $actualConfig = $this->_getPrivateProperty($curl, '_configData');
    $this->assertEquals('', $actualConfig['url']);
    $this->assertEquals(false, array_key_exists('new_property', $actualConfig));

    $curl->updateConfig(array('url' => 'new_url', 'new_property' => 'new_value'));

    $actualConfig = $this->_getPrivateProperty($curl, '_configData');
    $this->assertEquals('new_url', $actualConfig['url']);
    $this->assertEquals('new_value', $actualConfig['new_property']);
  }

  /**
   * @expectedException \Exception
   * @expectedExceptionMessage CURLE_OK indicates success: cannot retry on this error code.
   * @expectedExceptionCode \Securetrading\Http\CurlException::CODE_BAD_RETRY_CONFIG
   */
  public function testUpdateConfig_WhenCurlCanRetryOnSuccess() {
    $curl = $this->_newInstance();
    $curl->updateConfig(array('retry_curl_error_codes' => array(CURLE_UNSUPPORTED_PROTOCOL, CURLE_OK)));
  }
    
  /**
   *
   */
  public function test_SendAndReceive() { // Note - Not all logic from this function unit tested.
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_exec', true);
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_errno', 0);

    $this->_logMock
      ->expects($this->exactly(2))
      ->method('info')
      ->withConsecutive(
        array($this->equalTo('Beginning HTTP request to http://www.test.com.')),
	array($this->equalTo('Finished HTTP request to http://www.test.com.'))
      )
    ;

    $curl = $this->_newInstance(array(
      'connect_timeout' => 5,
      'sleep_seconds' => 1,
      'connect_attempts_timeout' => 19,
      'connect_attempts' => 4,
      'url' => 'http://www.test.com',
    ));

    $this->_($curl, '_sendAndReceive');
  }

  /**
   * @dataProvider provider_SendAndReceiveWithRetries_WhenNoRetries
   */
    public function test_SendAndReceiveWithRetries_WhenNoRetries($curlErrorCode, $curlErrorCodesToRetry = array()) {
    $curlExecCallCount = 0;
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_errno', $curlErrorCode);
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_exec', function() use(&$curlExecCallCount) {
      $curlExecCallCount++;
      return 'fake_http_response_body';
    });
    
    $this->_logMock
      ->expects($this->never())
      ->method('error');

    $curlConfig = array(
      'connect_timeout' => 5,
      'sleep_seconds' => 1,
      'connect_attempts_timeout' => 19,
      'connect_attempts' => 4,
      'url' => 'http://www.test.com',
    );

    if (!empty($curlErrorCodesToRetry)) {
        $curlConfig['retry_curl_error_codes'] = $curlErrorCodesToRetry;
    }
    
    $curl = $this->_newInstance($curlConfig);

    $actualReturnValue = $this->_($curl, '_sendAndReceiveWithRetries');
    $this->assertEquals('fake_http_response_body', $actualReturnValue);
    $this->assertEquals(1, $curlExecCallCount);
  }

  public function provider_SendAndReceiveWithRetries_WhenNoRetries()
  {
      $this->_addDataSet(0);
      $this->_addDataSet(CURLE_COULDNT_CONNECT, array(CURLE_LDAP_SEARCH_FAILED));
      return $this->_getDataSets();
  }

  /**
   * @dataProvider provider_SendAndReceiveWithRetries
   */
  public function test_SendAndReceiveWithRetries($curlErrorCode, $curlErrorCodesToRetry = array()) {
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_errno', $curlErrorCode);
    $i = -1;
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('time', function() use (&$i) { $times = array(10, 15, 20, 25, 30); return $times[++$i]; });
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('sleep', 0);
    
    $this->_logMock
      ->expects($this->exactly(4))
      ->method('error')
      ->withConsecutive(
        array(sprintf('Failed to connect to http://www.test.com on attempt 1.  Max attempts: 4.  Connect attempts timeout: 19.  cURL error: %s.  Sleeping for 1 second(s).', $curlErrorCode)),
        array(sprintf('Failed to connect to http://www.test.com on attempt 2.  Max attempts: 4.  Connect attempts timeout: 19.  cURL error: %s.  Sleeping for 1 second(s).', $curlErrorCode)),
        array(sprintf('Failed to connect to http://www.test.com on attempt 3.  Max attempts: 4.  Connect attempts timeout: 19.  cURL error: %s.  Sleeping for 1 second(s).', $curlErrorCode)),
	    array(sprintf('Failed to connect to http://www.test.com on attempt 4.  Max attempts: 4.  Connect attempts timeout: 19.  cURL error: %s.  Sleeping for 1 second(s).', $curlErrorCode))
      )
    ;

    $curlConfig = array(
      'connect_timeout' => 5,
      'sleep_seconds' => 1,
      'connect_attempts_timeout' => 19,
      'connect_attempts' => 4,
      'url' => 'http://www.test.com',
    );

    if (!empty($curlErrorCodesToRetry)) {
        $curlConfig['retry_curl_error_codes'] = $curlErrorCodesToRetry;
    }

    $curl = $this->_newInstance($curlConfig);
    
    $args = array(
      array(CURLOPT_CONNECTTIMEOUT, 3, array(5, 5, 4)),
    );
    $that = $this;

    $this->_testingCurlSetoptWrapper($args, function() use ($that, $curl) {
      $actualReturnValue = $that->_($curl, '_sendAndReceiveWithRetries');
      $that->assertEquals('curl_exec_rv', $actualReturnValue);
    });
  }

  public function provider_SendAndReceiveWithRetries() {
    $this->_addDataSet(CURLE_COULDNT_CONNECT);
    $this->_addDataSet(CURLE_COULDNT_RESOLVE_HOST);
    $this->_addDataSet(CURLE_COULDNT_RESOLVE_PROXY);
    $this->_addDataSet(CURLE_HTTP_POST_ERROR, array(CURLE_LDAP_SEARCH_FAILED, CURLE_HTTP_POST_ERROR));
    return $this->_getDataSets();
  }
    
  /**
   *
   */
  public function test_exec() {
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_exec', true);
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_errno', 0);
    $returnValue = $this->_($this->_newInstance(), '_exec');
    $this->assertEquals(array(true, 0), $returnValue);
  }

  /**
   * @dataProvider provider_getConnectionAttemptTimeout
   */
  public function test_getConnectionAttemptTimeout($startTime, $expectedReturnValue) {
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('time', 100);
    $curl = $this->_newInstance(array(
      'connect_attempts_timeout' => 60,
      'connect_timeout' => 5,
    ));
    $actualReturnValue = $this->_($curl, '_getConnectionAttemptTimeout', $startTime);
    $this->assertEquals($expectedReturnValue, $actualReturnValue);
  }

  public function provider_getConnectionAttemptTimeout() {
    $this->_addDataSet(90, 5);
    $this->_addDataSet(41, 1);
    $this->_addDataSet(40, 0);
    $this->_addDataSet(39, -1);
    return $this->_getDataSets();
  }

  /**
   * @dataProvider provider_canRetry
   */
  public function test_canRetry($connectionAttemptTimeout, $i, $expectedReturnValue) {
    $curl = $this->_newInstance(array(
      'connect_attempts' => 5,
    ));
    $actualReturnValue = $this->_($curl, '_canRetry', $connectionAttemptTimeout, $i);
    $this->assertEquals($expectedReturnValue, $actualReturnValue);
  }

  public function provider_canRetry() {
    $this->_addDataSet(1, 1, true);
    $this->_addDataSet(0, 1, false);
    $this->_addDataSet(-1, 1, false);
    $this->_addDataSet(1, 6, false);
    $this->_addDataSet(0, 6, false);
    $this->_addDataSet(-1, 6, false);
    return $this->_getDataSets();
  }

  /**
   * @expectedException \Securetrading\Http\CurlException
   * @expectedExceptionCode \Securetrading\Http\CurlException::CODE_BAD_RESULT
   */
  public function test_checkResult_WhenError() {
    $this->_($this->_newInstance(), '_checkResult', false);
  }

  /**
   *
   */
  public function test_checkResult_WhenNoError() {
    $curl = $this->_newInstance();
    $returnValue = $this->_($curl, '_checkResult', true);
    $this->assertSame($curl, $returnValue);
  }
}