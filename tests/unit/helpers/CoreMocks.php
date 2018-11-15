<?php

namespace Securetrading\Http;

function time() {
  return \Securetrading\Unittest\CoreMocker::runCoreMock('time');
}

function curl_setopt($ch, $key, $value) {
  return \Securetrading\Unittest\CoreMocker::runCoreMock('curl_setopt', array($ch, $key, $value));
}

function curl_setopt_array($ch, array $data) {
  return \Securetrading\Unittest\CoreMocker::runCoreMock('curl_setopt_array', array($ch, $data));
}

function curl_exec($ch) {
  return \Securetrading\Unittest\CoreMocker::runCoreMock('curl_exec', array($ch));
}

function curl_errno($ch) {
  return \Securetrading\Unittest\CoreMocker::runCoreMock('curl_errno', array($ch));
}

function curl_getinfo($ch, $constant) {
  return \Securetrading\Unittest\CoreMocker::runCoreMock('curl_getinfo', array($ch, $constant));
}

function file_get_contents($file) {
  return \Securetrading\Unittest\CoreMocker::runCoreMock('file_get_contents', array($file));
}

function sleep($s) {
    return \Securetrading\Unittest\CoreMocker::runCoreMock('sleep', array($s));
}