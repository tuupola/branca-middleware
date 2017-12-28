# PSR-7 and PSR-15 Branca Authentication Middleware

[![Latest Version](https://img.shields.io/packagist/v/tuupola/branca-middleware.svg?style=flat-square)](https://packagist.org/packages/tuupola/branca-middleware)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/tuupola/branca-middleware/master.svg?style=flat-square)](https://travis-ci.org/tuupola/branca-middleware)
[![Coverage](https://img.shields.io/codecov/c/github/tuupola/branca-middleware/master.svg?style=flat-square)](https://codecov.io/github/tuupola/branca-middleware/branch/master)

This middleware implements [Branca token](https://github.com/tuupola/branca-spec) authentication. Branca is similar to JWT but more secure and has smaller token size. The middleware can be used with any framework using PSR-7 or PSR-15 style middlewares. It has been tested with [Slim Framework](http://www.slimframework.com/) and [Zend Expressive](https://zendframework.github.io/zend-expressive/).

You might also be interested in reading [Branca as an Alternative to JWT?](https://appelsiini.net/2017/branca-alternative-to-jwt/.)

This middleware does **not** implement OAuth authorization server nor does it provide ways to generate, issue or store the authentication tokens. It only parses and authenticates a token when passed via header or cookie.

## Install

Install latest version using [composer](https://getcomposer.org/).

``` bash
$ composer require tuupola/branca-middleware
```

If using Apache add the following to the `.htaccess` file. Otherwise PHP wont have access to `Authorization: Bearer` header.

``` bash
RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

## Usage

Configuration options are passed as an array. The only mandatory parameter is 32 byte `secret` which is used for authenticating and encrypting the token.

For simplicity's sake examples show `secret` hardcoded in code. In real life you should store it somewhere else. Good option is environment variable. You can use [dotenv](https://github.com/vlucas/phpdotenv) or something similar for development. Examples assume you are using Slim Framework.

``` php
$app->add(new Tuupola\Middleware\BrancaAuthentication([
    "secret" => "supersecretkeyyoushouldnotcommit"
]));
```

An example where your secret is stored as an environment variable:

``` php
$app->add(new Tuupola\Middleware\BrancaAuthentication([
    "secret" => getenv("BRANCA_SECRET")
]));
```

When a request is made, the middleware tries to validate and decode the token. If a token is not found or there is an error when validating and decoding it, the server will respond with `401 Unauthorized`.

Validation errors are triggered when the token has been tampered with or optionally if the token has expired.

## Optional parameters
### Path

The optional `path` parameter allows you to specify the protected part of your website. It can be either a string or an array. You do not need to specify each URL. Instead think of `path` setting as a folder. In the example below everything starting with `/api` will be authenticated. If you do not define `path` all routes will be protected.

``` php
$app->add(new Tuupola\Middleware\BrancaAuthentication([
    "path" => "/api", /* or ["/api", "/admin"] */
    "secret" => "supersecretkeyyoushouldnotcommit"
]));
```

### Ignore

With optional `ignore` parameter you can make exceptions to `path` parameter. In the example below everything starting with `/api` and `/admin`  will be authenticated with the exception of `/api/token` and `/admin/ping` which will not be authenticated.

``` php
$app->add(new Tuupola\Middleware\BrancaAuthentication([
    "path" => ["/api", "/admin"],
    "ignore" => ["/api/token", "/admin/ping"],
    "secret" => "supersecretkeyyoushouldnotcommit"
]));
```

### TTL

Branca tokens have a creation timestamp embedded in the header. You can control how old token your application accepts with the `ttl` parameter.

``` php
$app->add(new Tuupola\Middleware\BrancaAuthentication([
    "ttl" => 3600, /* 60 minutes */
    "secret" => "supersecretkeyyoushouldnotcommit"
]));
```

### Header

By default middleware tries to find the token from `Authorization` header. You can change header name using the `header` parameter.

``` php
$app->add(new Tuupola\Middleware\BrancaAuthentication([
    "header" => "X-Token",
    "secret" => "supersecretkeyyoushouldnotcommit"
]));
```

### Regexp

By default the middleware assumes the value of the header is in `Bearer <token>` format. You can change this behaviour with `regexp` parameter. For example if you have custom header such as `X-Token: <token>` you should pass both header and regexp parameters.

``` php
$app->add(new Tuupola\Middleware\BrancaAuthentication([
    "header" => "X-Token",
    "regexp" => "/(.*)/",
    "secret" => "supersecretkeyyoushouldnotcommit"
]));
```

### Cookie

If token is not found from neither environment or header, the middleware tries to find it from cookie named `token`. You can change cookie name using `cookie` parameter.

``` php
$app->add(new Tuupola\Middleware\BrancaAuthentication([
    "cookie" => "nekot",
    "secret" => "supersecretkeyyoushouldnotcommit"
]));
```

### Logger

The optional `logger` parameter allows you to pass in a PSR-3 compatible logger to help with debugging or other application logging needs.

``` php
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

$app = new Slim\App;

$logger = new Logger("slim");
$rotating = new RotatingFileHandler(__DIR__ . "/logs/slim.log", 0, Logger::DEBUG);
$logger->pushHandler($rotating);

$app->add(new Tuupola\Middleware\BrancaAuthentication([
    "path" => "/api",
    "logger" => $logger,
    "secret" => "supersecretkeyyoushouldnotcommit"
]));
```

### Before

Before funcion is called only when authentication succeeds but before the next incoming middleware is called. You can use this to alter the request before passing it to the next incoming middleware in the stack. If it returns anything else than `Psr\Http\Message\RequestInterface` the return value will be ignored.

``` php
$app->add(new Tuupola\Middleware\BrancaAuthentication([
    "secret" => "supersecretkeyyoushouldnotcommit",
    "before" => function ($request, $arguments) {
        return $request->withAttribute("test", "test");
    }
]));
```

### After

After function is called only when authentication succeeds and after the incoming middleware stack has been called. You can use this to alter the response before passing it next outgoing middleware in the stack. If it returns anything else than `Psr\Http\Message\ResponseInterface` the return value will be ignored.


``` php
$app->add(new Tuupola\Middleware\BrancaAuthentication([
    "secret" => "supersecretkeyyoushouldnotcommit",
    "after" => function ($response, $arguments) {
        return $response->withHeader("X-Brawndo", "plants crave");
    }
]));
```

### Error

Error is called when authentication fails. It receives last error message in arguments. You can use this for example to return JSON formatted error responses.

```php
$app->add(new Tuupola\Middleware\BrancaAuthentication([
    "secret" => "supersecretkeyyoushouldnotcommit",
    "error" => function ($response, $arguments) {
        $data["status"] = "error";
        $data["message"] = $arguments["message"];
        return $response
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
]));
```

### Rules

The optional `rules` parameter allows you to pass in rules which define whether the request should be authenticated or not. A rule is a callable which receives the request as parameter. If any of the rules returns boolean `false` the request will not be authenticated.

By default middleware configuration looks like this. All paths are authenticated with all request methods except `OPTIONS`.

``` php
$app->add(new Tuupola\Middleware\BrancaAuthentication([
    "rules" => [
        new Tuupola\Middleware\BrancaAuthentication\RequestPathRule([
            "path" => "/",
            "ignore" => []
        ]),
        new Tuupola\Middleware\BrancaAuthentication\RequestMethodRule([
            "ignore" => ["OPTIONS"]
        ])
    ]
]));
```

RequestPathRule contains both a `path` parameter and a `ignore` parameter. Latter contains paths which should not be authenticated. RequestMethodRule contains `ignore` parameter of request methods which also should not be authenticated. Think of `ignore` as a whitelist.

99% of the cases you do not need to use the `rules` parameter. It is only provided for special cases when defaults do not suffice.

## Security

Branca tokens are essentially passwords. You should treat them as such and you should always use HTTPS. If the middleware detects insecure usage over HTTP it will throw a `RuntimeException`. This rule is relaxed for requests on localhost. To allow insecure usage you must enable it manually by setting `secure` to `false`.

``` php
$app->add(new Tuupola\Middleware\BrancaAuthentication([
    "secure" => false,
    "secret" => "supersecretkeyyoushouldnotcommit"
]));
```

Alternatively you can list your development host to have relaxed security.

``` php
$app->add(new Tuupola\Middleware\BrancaAuthentication([
    "secure" => true,
    "relaxed" => ["localhost", "dev.example.com"],
    "secret" => "supersecretkeyyoushouldnotcommit"
]));
```

## Authorization

By default middleware only authenticates. This is not very interesting by itself. Beauty of Branca is that you can pass extra data in the token. This data can include for example scope which can be used for authorization.

**It is up to you to implement how token data is stored or possible authorization implemented.**

Let assume you have JSON encoded payload which includes requested scope and uid. Using the `before` callback you can inject the unencoded payload to the request object.

``` php
[
    "uid" => 123,
    "scope" => ["read", "write", "delete"]
]
```

``` php
$app->add(new Tuupola\Middleware\BrancaAuthentication([
    "secret" => "supersecretkeyyoushouldnotcommit",
    "before" => function ($request, $response, $arguments) {
        $payload = json_decode($arguments["payload"], true);
        return $request->withAttribute("token", $payload);
    }
]));

$app->delete("/item/{id}", function ($request, $response, $arguments) {
    if (in_array("delete", $request->token->scope)) {
        /* Code for deleting item */
    } else {
        /* No scope so respond with 401 Unauthorized */
        return $response->withStatus(401);
    }
});
```

## Testing

You can run tests either manually or automatically on every code change. Automatic tests require [entr](http://entrproject.org/) to work.

``` bash
$ make test
```
``` bash
$ brew install entr
$ make watch
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email tuupola@appelsiini.net instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
