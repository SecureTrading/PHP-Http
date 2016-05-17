<?php

return array(
  'stHttp' => array(
    'definitions' => array(
      '\Securetrading\Http\Curl' => function(\Securetrading\Ioc\IocInterface $ioc, $alias, $params) {
	return new \Securetrading\Http\Curl($ioc->get('stLog'), $ioc->getParameter('config', $params));
      },
    ),
  ),
);