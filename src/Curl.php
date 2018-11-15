<?php

namespace Securetrading\Http;

class Curl implements HttpInterface {
  protected $_log;

  protected $_configData = array(
    'url' => '',
    'user_agent' => '',
    'ssl_verify_peer' => true,
    'ssl_verify_host' => 2,
    'connect_timeout' => 10,
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
    'retry_curl_error_codes' => array(CURLE_COULDNT_CONNECT, CURLE_COULDNT_RESOLVE_HOST, CURLE_COULDNT_RESOLVE_PROXY),
  );

  protected $_ch;

  protected $_tempStream;

  protected $_httpLogData = '';

  public function __construct(\Psr\Log\LoggerInterface $log, array $configData = array()) {
    $this->_log = $log;
    $this->updateConfig($configData);
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

  public function updateConfig($configData = array()) {
      if (array_key_exists('retry_curl_error_codes', $configData) && in_array(CURLE_OK, $configData['retry_curl_error_codes'])) {
          throw new CurlException("CURLE_OK indicates success: cannot retry on this error code.", CurlException::CODE_BAD_RETRY_CONFIG);
      }
    $this->_configData = array_replace($this->_configData, $configData);
  }

  public function getInfo($curlInfoConstant = 0) {
    return curl_getinfo($this->_ch, $curlInfoConstant);
  }

  protected function _prepareCurl() {
    curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, true);

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

    curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        
    curl_setopt_array($this->_ch, $this->_configData['curl_options']);
  }
  
  protected function _sendAndReceive() {
    $this->_log->info(sprintf('Beginning HTTP request to %s.', $this->_configData['url']));

    $result = $this->_sendAndReceiveWithRetries();

    $this->_log->info(sprintf('Finished HTTP request to %s.', $this->_configData['url']));

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
      list($execResult, $curlErrorCode) = $this->_exec();
      
      if (in_array($curlErrorCode, $this->_configData['retry_curl_error_codes'])) {
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
	$newTimeout = $this->_getConnectionAttemptTimeout($startTime);
	if ($this->_canRetry($newTimeout, $i)) {
	  curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, $newTimeout);
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

  protected function _getConnectionAttemptTimeout($startTime) {
    $timeRemaining = $this->_configData['connect_attempts_timeout'] - (time() - $startTime);
    return min($timeRemaining, $this->_configData['connect_timeout']);
  }

  protected function _canRetry($connectionAttemptTimeout, $i) {
    return ($connectionAttemptTimeout > 0) && ($i < $this->_configData['connect_attempts']);
  }

  protected function _checkResult($result) {
    if ($result === false) {
      throw new CurlException(sprintf("cURL Error Code: '%s'.  Error Message: '%s'.", curl_errno($this->_ch), curl_error($this->_ch)), CurlException::CODE_BAD_RESULT);
    }
    return $this;
  }
}