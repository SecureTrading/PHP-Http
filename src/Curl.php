<?php

namespace Securetrading\Http;

class Curl implements HttpInterface {
  protected $_log;

  protected $_configData = array(
    'url' => '',
    'user_agent' => '',
    'ssl_verify_peer' => true,
    'ssl_verify_host' => 2,
    'connect_timeout' => 5,
    'timeout' => 60,
    'http_headers' => array(),
    'ssl_cacertfile' => '',
    'proxy_host' => '',
    'proxy_port' => '',
    'username' => '',
    'password' => '',
    'curl_options' => array(),

    // Not CURLOPT_* constants:

    'sleep_seconds' => 1,
    'connect_attempts' => 20,
    'connect_attempts_timeout' => 40,
  );

  protected $_ch;

  protected $_tempStream;

  protected $_httpLogData = '';

  public function __construct(\Psr\Log\LoggerInterface $log, array $configData = array()) {
    $this->_log = $log;
    $this->_configData = array_replace($this->_configData, $configData);
    $this->_ch = curl_init();
    $this->_tempStream = fopen('php://temp', "w+");
  }

  public function send($requestMethod, $requestBody = '') {
    curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, $requestMethod);
    if (!empty($requestBody)) {
      curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $requestBody);
    }
    $this->_prepareCurl();
    return $this->_sendAndReceive();
  }

  public function get() {
    $this->_prepareCurl();
    return $this->_sendAndReceive();
  }

  public function post($requestBody = '') {
    curl_setopt($this->_ch, CURLOPT_POST, 1);
    curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $requestBody);
    $this->_prepareCurl();
    return $this->_sendAndReceive();
  }

  public function getResponseCode() {
    return curl_getinfo($this->_ch, CURLINFO_HTTP_CODE);
  }
  
  public function getLogData() {
    return $this->_httpLogData;
  }

  public function getInfo($curlInfoConstant = 0) {
    return curl_getinfo($this->_ch, $curlInfoConstant);
  }

  protected function _prepareCurl() {
    curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($this->_ch, CURLOPT_FAILONERROR, true);

    curl_setopt($this->_ch, CURLOPT_URL, $this->_configData['url']);
    curl_setopt($this->_ch, CURLOPT_USERAGENT, $this->_configData['user_agent']);
    curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, $this->_configData['ssl_verify_peer']);
    curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, $this->_configData['ssl_verify_host']);
    curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, $this->_configData['connect_timeout']);
    curl_setopt($this->_ch, CURLOPT_TIMEOUT, $this->_configData['timeout']);
    curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $this->_configData['http_headers']);
    
    curl_setopt($this->_ch, CURLOPT_VERBOSE, true);
    curl_setopt($this->_ch, CURLOPT_STDERR, $this->_tempStream);
    
    if (!empty($this->_configData['ssl_cacertfile'])) {
      curl_setopt($this->_ch, CURLOPT_CAINFO, $this->_configData['ssl_cacertfile']);
    }

    if (!empty($this->_configData['proxy_host'])) {
      curl_setopt($this->_ch, CURLOPT_PROXY, $this->_configData['proxy_host']);
    }

    if (!empty($this->_configData['proxy_port'])) {
      curl_setopt($this->_ch, CURLOPT_PROXYPORT, $this->_configData['proxy_port']);
    }

    if (!empty($this->_configData['username']) && !empty($this->_configData['password'])) {
      curl_setopt($this->_ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($this->_ch, CURLOPT_USERPWD, $this->_configData['username'] . ':' . $this->_configData['password']);
    }
    
    curl_setopt_array($this->_ch, $this->_configData['curl_options']);
    
    curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
  }
  
  protected function _sendAndReceive() {
    $result = $this->_sendAndReceiveWithRetries();
    
    rewind($this->_tempStream);
    $this->_httpLogData = stream_get_contents($this->_tempStream);

    $this->_checkResult($result);
    return $result;
  }

  protected function _sendAndReceiveWithRetries() {
    $i = 0;
    $startTime = time();
    $execResult = null;
    
    while (true) {
      $i++;
      $canRetry = false;
      list($execResult, $curlErrorCode) = $this->_exec();
      
      if (in_array($curlErrorCode, array(CURLE_COULDNT_CONNECT))) {
	$errorMessage = sprintf(
	  'Failed to connect to %s on attempt %s.  Max attempts: %s.  Connect attempts timeout: %s.  cURL error: %s.  Sleeping for %s second(s).',
	  $this->_configData['url'],
	  $i,
	  $this->_configData['connect_attempts'],
	  $this->_configData['connect_attempts_timeout'],
	  $curlErrorCode,
	  $this->_configData['sleep_seconds']
	);
	$this->_log->error($errorMessage);
	sleep($this->_configData['sleep_seconds']);
	if ($this->_canRetry($startTime, $i)) {
	  continue;
	}
      }
      break;
    }
    return $execResult;
  }

  protected function _exec() {
    $httpResponseBody = curl_exec($this->_ch);
    $curlErrorCode = curl_errno($this->_ch);
    return array($httpResponseBody, $curlErrorCode);
  }
  
  protected function _canRetry($startTime, $i) {
    $timeElapsed = time() - $startTime;
    return (
      $timeElapsed + $this->_configData['connect_timeout'] + $this->_configData['sleep_seconds'] <= $this->_configData['connect_attempts_timeout'] 
      &&
      $i < $this->_configData['connect_attempts']
    );
  }

  protected function _checkResult($result) {
    if ($result === false) {
      throw new CurlException(sprintf("cURL Error Code: '%s'.  Error Message: '%s'.", curl_errno($this->_ch), curl_error($this->_ch)), CurlException::CODE_BAD_RESULT);
    }
    return $this;
  }
}