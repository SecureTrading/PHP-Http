<?php

namespace Securetrading\Http;

class Facade {
  public static function configureHttp($instance, $key, $config) {
    if ($config->has($key . 'username')) {
      $instance->setUsername($config->get($key . 'username'));
    }
    
    if ($config->has($key . 'password')) {
      $instance->setPassword($config->get($key . 'password'));
    }

    if ($config->has($key . 'proxy_host')) {
      $instance->setProxyHost($config->get($key . 'proxy_host'));
    }

    if ($config->has($key . 'proxy_port')) {
      $instance->setProxyPort($config->get($key . 'proxy_port'));
    }

    if ($config->has($key . 'ssl_verify_peer')) {
      $instance->setSslVerifyPeer($config->get($key . 'ssl_verify_peer'));
    }
    
    if ($config->has($key . 'ssl_verify_host')) {
      $instance->setSslVerifyHost($config->get($key . 'ssl_verify_host'));
    }
    
    if ($config->has($key . 'ssl_cacertfile')) {
      $instance->setSslCaCertFile($config->get($key . 'ssl_cacertfile'));
    }
    
    if ($config->has($key . 'connect_timeout')) {
      $instance->setConnectTimeout($config->get($key . 'connect_timeout'));
    }
    
    if ($config->has($key . 'timeout')) {
      $instance->setTimeout($config->get($key . 'timeout'));
    }
    
    if ($config->has($key . 'connect_attempts')) {
      $instance->setConnectAttempts($config->get($key . 'connect_attempts'));
    }

    if ($config->has($key . 'connect_attempts_timeout')) {
      $instance->setConnectAttemptsTimeout($config->get($key . 'connect_attempts_timeout'));
    }

    if ($config->has($key . 'user_agent')) {
      $instance->setConnectAttemptsTimeout($config->get($key . 'user_agent'));
    }

    if ($config->has($key . 'sleep_useconds')) {
      $instance->setSleepUseconds($config->get($key . 'sleep_useconds'));
    }
    
    if ($config->has($key . 'curl_options')) {
      $instance->setCurlOptions($config->get($key . 'curl_options'));
    }

    return $instance;
  }
}