# PHP-Http

A helper package used by other Secure Trading packages.

`Securetrading\Http\Curl` provides a wrapper around the core PHP cURL functions that contains connection retry and logging logic.  A `\Securetrading\Http\CurlException` is thrown if the call it makes to `curl_exec()` fails.

## Using This Package

Run this in the root directory of your application:

    composer require securetrading/http

## \Securetrading\Http\Curl - Usage

Instantiate the client like this:

    $http = new \Securetrading\Http\Curl($logger, $configData)
    
    # Where:
    # $logger implements \Psr\Log\LoggerInterface
    # $configData is a multidimensional array of config options.
    
Valid config options (and their default values) for the constructor are:

    array(
        'url' => '',
        'user_agent' => '',
        'ssl_verify_peer' => true,
        'ssl_verify_host' => 2,
        'connect_timeout' => 5,
        'timeout' => 60,
        'http_headers' => array(),
        'ssl_cacertfile' => '',
        'proxy_host' => '',
        'proxy_port' => '',
        'username' => '',
    '    password' => '',
        'curl_options' => array(),
        'sleep_seconds' => 1,
        'connect_attempts' => 20,
        'connect_attempts_timeout' => 40,
    );

Call `send($requestMethod, $requestBody = '')` to make HTTP requests.

`get()` and `post($requestBody = '')` helper methods have also been provided for GET and POST HTTP requests.
