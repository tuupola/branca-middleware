<?php

/**
 * This file is part of PSR-7 & PSR-15 Branca Authentication middleware
 *
 * Copyright (c) 2017 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/branca-middleware
 *
 */

namespace Tuupola\Middleware;

use Equip\Dispatch\MiddlewareCollection;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tuupola\Http\Factory\ResponseFactory;
use Tuupola\Http\Factory\ServerRequestFactory;
use Tuupola\Http\Factory\StreamFactory;

class BrancaAuthenticationTest extends TestCase
{
    /* @codingStandardsIgnoreStart */
    public static $token = "5R6aml4cIAcjZSiHu34R9ELfyk3IUEOuuHu53mbYFaBNkmCqjosw2ZD8FX4UimvNd5Ibf4Ytv3yGwALhCeYENT7ztu1Nv97h9nDT3ERWWqpf";
    /* @codingStandardsIgnoreEnd */

    public static $token_as_array = [
        "scope" => ["read", "write", "delete"]
    ];

    public function testShouldBeTrue()
    {
        $this->assertTrue(true);
    }

    public function testShouldReturn401WithoutToken()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api");

        $response = (new ResponseFactory)->createResponse();

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit"
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldReturn200WithTokenFromHeader()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api")
            ->withHeader("X-Token", "Bearer " . self::$token);

        $response = (new ResponseFactory)->createResponse();

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "header" => "X-Token"
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
    }

    public function testShouldReturn200WithTokenFromHeaderWithCustomRegexp()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api")
            ->withHeader("X-Token", self::$token);

        $response = (new ResponseFactory)->createResponse();

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "header" => "X-Token",
            "regexp" => "/(.*)/"
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
    }

    public function testShouldReturn200WithTokenFromCookie()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api")
            ->withCookieParams(["nekot" => self::$token]);

        $response = (new ResponseFactory)->createResponse();

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "cookie" => "nekot",
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
    }

    public function testShouldReturn401WithFalseFromAfter()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $response = (new ResponseFactory)->createResponse();

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "after" => function ($response, $arguments) {
                return $response
                    ->withBody((new StreamFactory)->createStream())
                    ->withStatus(401);
            }
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldAlterResponseWithAfter()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $response = (new ResponseFactory)->createResponse();

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "after" => function ($response, $arguments) {
                return $response->withHeader("X-Brawndo", "plants crave");
            }
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("plants crave", (string) $response->getHeaderLine("X-Brawndo"));
    }

    public function testShouldReturn200WithOptions()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api")
            ->withMethod("OPTIONS");

        $response = (new ResponseFactory)->createResponse();

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit"
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
    }

    public function testShouldReturn400WithInvalidToken()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api")
            ->withHeader("Authorization", "Bearer invalid" . self::$token);

        $response = (new ResponseFactory)->createResponse();

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit"
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldReturn200WithoutTokenWithPath()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/public");

        $response = (new ResponseFactory)->createResponse();

        $auth = new BrancaAuthentication([
            "path" => ["/api", "/foo"],
            "secret" => "supersecretkeyyoushouldnotcommit"
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
    }

    public function testShouldReturn200WithoutTokenWithIgnore()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api/ping");

        $response = (new ResponseFactory)->createResponse();

        $auth = new BrancaAuthentication([
            "path" => ["/api", "/foo"],
            "ignore" => ["/api/ping"],
            "secret" => "supersecretkeyyoushouldnotcommit"
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
    }

    public function testShouldNotAllowInsecure()
    {
        $this->expectException("RuntimeException");

        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "http://example.com/api")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $response = (new ResponseFactory)->createResponse();

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit"
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);
    }

    public function testShoulAllowInsecure()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "http://example.com/api")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $response = (new ResponseFactory)->createResponse();

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "secure" => false
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };


        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());

        $response = $auth($request, $response, $next);
    }

    public function testShouldRelaxInsecureInLocalhost()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "http://localhost/api")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $response = (new ResponseFactory)->createResponse();

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit"
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
    }

    public function testShouldRelaxInsecureInExampleCom()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "http://example.com/api")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $response = (new ResponseFactory)->createResponse();

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "relaxed" => ["example.com"],
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
    }

    public function testShouldCallAfter()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $response = (new ResponseFactory)->createResponse();

        $dummy = null;
        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "after" => function ($response, $arguments) use (&$dummy) {
                $dummy = $arguments["decoded"];
            }
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
        $this->assertEquals(self::$token_as_array, json_decode($dummy, true));
    }

    public function testShouldCallError()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api");

        $response = (new ResponseFactory)->createResponse();

        $dummy = null;
        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "error" => function ($request, $response, $arguments) use (&$dummy) {
                $dummy = true;
            }
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
        $this->assertTrue($dummy);
    }

    public function testShouldCallErrorAndModifyBody()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api");

        $response = (new ResponseFactory)->createResponse();

        $dummy = null;
        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "error" => function ($request, $response, $arguments) use (&$dummy) {
                $dummy = true;
                $response->getBody()->write("Error");
                return $response;
            }
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("Error", $response->getBody());
        $this->assertTrue($dummy);
    }

    // public function testShouldLog()
    // {
    //     $logger = new \Psr\Log\NullLogger;
    //     $auth = new \Tuupola\Middleware\BrancaAuthentication([
    //         "logger" => $logger
    //     ]);
    //     $this->assertNull($auth->log(\Psr\Log\LogLevel::WARNING, "Token not found"));
    // }

    public function testShouldAllowUnauthenticatedHttp()
    {

        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/public/foo");

        // $request = (new ServerRequest)
        //     ->withUri(new Uri("http://example.com/public/foo"))
        //     ->withMethod("GET");

        $response = (new ResponseFactory)->createResponse();

        $auth = new \Tuupola\Middleware\BrancaAuthentication([
            "path" => ["/api", "/bar"],
            "secret" => "supersecretkeyyoushouldnotcommit"
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn401FromAfter()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api")
            ->withHeader("Authorization", "Bearer " . self::$token);

        // $request = (new ServerRequest)
        //     ->withUri(new Uri("https://example.com/api"))
        //     ->withMethod("GET")
        //     ->withHeader("Authorization", "Bearer " . self::$token);

        $response = (new ResponseFactory)->createResponse();

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "after" => function ($response, $arguments) {
                return $response
                    ->withBody((new StreamFactory)->createStream())
                    ->withStatus(401);
            }
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldModifyRequestUsingBefore()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $response = (new ResponseFactory)->createResponse();

        $dummy = null;
        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "before" => function ($request, $arguments) {
                return $request->withAttribute("test", "test");
            }
        ]);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $test = $request->getAttribute("test");
            $response->getBody()->write($test);
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("test", (string) $response->getBody());
    }

    public function testShouldHandlePsr15()
    {
        // if (!class_exists("Equip\Dispatch\MiddlewareCollection")) {
        //     $this->markTestSkipped(
        //         "MiddlewareCollection class is not available."
        //     );
        // }

        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/");

        $response = (new ResponseFactory)->createResponse();

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit"
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }
}
