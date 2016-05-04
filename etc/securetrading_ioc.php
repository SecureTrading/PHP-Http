<?php

return array(
  'stHttp' => array(
    'definitions' => array(
      #'stHttp' => '\Securetrading\Http\Curl', #TODOPBNEW-old
      '\Securetrading\Http\Curl' => function(\Securetrading\Ioc\IocInterface $ioc, $alias, $params) {#TODOPBNEW
	return new \Securetrading\Http\Curl($ioc->get('stLog'));
      },
    ),
  ),
);