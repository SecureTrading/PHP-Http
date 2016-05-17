<?php

namespace Securetrading\Http;

class Helper implements HelperInterface {
  public function retrieveGetParams() {
    return $this->_retrieveHttpRequestBodyParams($_SERVER['QUERY_STRING']);
  }
  
  public function retrievePostParams() {
    return $this->_retrieveHttpRequestBodyParams(file_get_contents("php://input"));
  }
  
  protected function _retrieveHttpRequestBodyParams($dataString) {
    $data = explode('&', $dataString);
    $params = array();
    foreach($data as $param) {
      list($name, $value) = explode('=', $param);
      $params[urldecode($name)][] = urldecode($value);
    }
    return $params;
  }
}