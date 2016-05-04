<?php

namespace Securetrading\Http;

interface HttpInterface {
  function setUrl($url);
  function getUrl();
  function setRequestHeaders(array $requestHeaders);
  function addRequestHeader($header);
  function getRequestHeaders();
  public function send($requestMethod, $requestBody = '');
  public function get();
  public function post($requestBody = '');
  public function getLogData();
  public function getResponseCode();
}