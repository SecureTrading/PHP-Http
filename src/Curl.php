<?php

namespace Securetrading\Http;

class Curl implements HttpInterface {
  protected $_log;

  protected $_ch;

  protected $_url = '';
    
  protected $_username = '';
   
  protected $_password = '';

  protected $_userAgent = '';

  protected $_proxyHost = '';

  protected $_proxyPort;
    
  protected $_sslVerifyPeer = true;
    
  protected $_sslVerifyHost = 2;
  
  protected $_sslCaCertFile = '';
  
  protected $_curlOptions = array();
  
  protected $_httpHeaders = array();
  
  protected $_connectTimeout = 5;
  
  protected $_timeout = 60;
  
  protected $_connectAttempts = 20;

  protected $_connectAttemptsTimeout = 40;

  protected $_sleepUseconds = 1000000;

  protected $_httpLogData = '';

  protected $_tempStream;

  public function __construct(\Psr\Log\LoggerInterface $log) {
    $this->_log = $log;
    $this->_ch = curl_init();
    $this->_tempStream = fopen('php://temp', "w+");
  }
    
  public function setUsername($username) {
    $this->_username = $username;
    return $this;
  }
    
  public function setPassword($password) {
    $this->_password = $password;
    return $this;
  }

  public function setUserAgent($userAgent) {
    $this->_userAgent = $userAgent;
    return $this;
  }

  public function setProxyHost($proxyHost) {
    $this->_proxyHost = $proxyHost;
    return $this;
  }

  public function setProxyPort($proxyPort) {
    $this->_proxyPort = $proxyPort;
    return $this;
  }
    
  public function setSslVerifyPeer($bool) {
    $this->_sslVerifyPeer = (bool) $bool;
    return $this;
  }
  
  public function setSslVerifyHost($int) {
    $this->_sslVerifyHost = $int;
    return $this;
  }
  
  public function setSslCaCertFile($file) {
    $this->_sslCaCertFile = $file;
    return $this;
  }

  public function setConnectTimeout($connectTimeout) {
    $this->_connectTimeout = $connectTimeout;
    return $this;
  }

  public function setConnectAttemptsTimeout($connectAttemptsTimeout) {
    $this->_connectAttemptsTimeout = $connectAttemptsTimeout;
    return $this;
  }
  
  public function setTimeout($timeout) {
    $this->_timeout = $timeout;
    return $this;
  }
  
  public function setConnectAttempts($connectAttempts) {
    $this->_connectAttempts = $connectAttempts;
    return $this;
  }
  
  public function setSleepUseconds($sleepUseconds) {
    $this->_sleepUseconds = $sleepUseconds;
    return $this;
  }
  
  public function setCurlOptions(array $options) {
    $this->_curlOptions = $options;
    return $this;
  }

  public function setCurlOption($option, $value) {
    $this->_curlOptions[$option] = $value;
    return $this;
  }

  public function setUrl($url) {
    $this->_url = $url;
    return $this;
  }
  
  public function getUrl() {
    return $this->_url;
  }

  public function setRequestHeaders(array $headers) {
    $this->_httpHeaders = $headers;
    return $this;
  }
  
  public function addRequestHeader($header) {
    $this->_httpHeaders[] = $header;
    return $this;
  }
  
  public function getRequestHeaders() {
    return $this->_httpHeaders;
  }

  public function send($requestMethod, $requestBody = '') {
    curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, $requestMethod);
    if (!empty($requestBody)) {
      curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $requestBody);
    }
    $this->_prepareCurl();
    return $this->_sendAndReceive();
  }

  public function post($requestBody = '') {
    curl_setopt($this->_ch, CURLOPT_POST, 1);
    curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $requestBody);
    $this->_prepareCurl();
    return $this->_sendAndReceive();
  }

  public function get() {
    $this->_prepareCurl();
    return $this->_sendAndReceive();
  }

  public function getInfo($curlInfoConstant = 0) {
    return curl_getinfo($this->_ch, $curlInfoConstant);
  }

  public function getResponseCode() {
    return curl_getinfo($this->_ch, CURLINFO_HTTP_CODE);
  }
  
  public function getLogData() {
    return $this->_httpLogData;
  }

  protected function _prepareCurl() {
    curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($this->_ch, CURLOPT_FAILONERROR, true);

    curl_setopt($this->_ch, CURLOPT_URL, $this->_url);
    curl_setopt($this->_ch, CURLOPT_USERAGENT, $this->_userAgent);
    curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, $this->_sslVerifyPeer);
    curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, $this->_sslVerifyHost);
    curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, $this->_connectTimeout);
    curl_setopt($this->_ch, CURLOPT_TIMEOUT, $this->_timeout);
    curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $this->getRequestHeaders());
    
    curl_setopt($this->_ch, CURLOPT_VERBOSE, true);
    curl_setopt($this->_ch, CURLOPT_STDERR, $this->_tempStream);
    
    if (!empty($this->_sslCaCertFile)) {
      curl_setopt($this->_ch, CURLOPT_CAINFO, $this->_sslCaCertFile);
    }

    if (!empty($this->_proxyHost)) {
      curl_setopt($this->_ch, CURLOPT_PROXY, $this->_proxyHost);
    }

    if (!empty($this->_proxyPort)) {
      curl_setopt($this->_ch, CURLOPT_PROXYPORT, $this->_proxyPort);
    }

    if (!empty($this->_username) && !empty($this->_password)) {
      curl_setopt($this->_ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($this->_ch, CURLOPT_USERPWD, $this->_username . ':' . $this->_password);
    }
    
    curl_setopt_array($this->_ch, $this->_curlOptions);
    
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
	  'Failed to connect to %s on attempt %s.  Max attempts: %s.  Connect timeout: %s.  cURL Error: %s.  Sleeping for %s second(s).',
	  $this->_url, 
	  $i,
	  $this->_connectAttempts,
	  $this->_connectAttemptsTimeout,
	  $curlErrorCode,
	  $this->_microsecondsToSeconds($this->_sleepUseconds)
	);
	$this->_log->error($errorMessage);
	usleep($this->_sleepUseconds);
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
    return ($timeElapsed + $this->_connectTimeout + $this->_microsecondsToSeconds($this->_sleepUseconds)) <= $this->_connectAttemptsTimeout && $i < $this->_connectAttempts;
  }
  
  protected function _microsecondsToSeconds($useconds) {
    return $useconds / 1000000;
  }

  protected function _checkResult($result) {
    if ($result === false) {
      throw new CurlException(sprintf("cURL Error Code: '%s'.  Error Message: '%s'.", curl_errno($this->_ch), curl_error($this->_ch)), CurlException::CODE_BAD_RESULT);
    }
    return $this;
  }
}