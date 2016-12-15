<?php

namespace Securetrading\Http;

interface HttpInterface {
  public function send($requestMethod, $requestBody = '');
  public function get();
  public function post($requestBody = '');
  public function getResponseCode();
  public function getLogData();
  public function updateConfig($configData = array());
}