<?php

namespace Securetrading\Http;

class CurlException extends \Securetrading\Exception {
  const CODE_BAD_RETRY_CONFIG = 1;
  const CODE_BAD_RESULT = 2;
}