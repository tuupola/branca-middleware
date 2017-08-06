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

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Equip\Dispatch\MiddlewareCollection;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;
use Zend\Diactoros\Stream;

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
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");
        $response = new Response;

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit"
        ]);

        $next = function (ServerRequest $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldReturn200WithTokenFromHeader()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET")
            ->withHeader("X-Token", "Bearer " . self::$token);

        $response = new Response;

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "header" => "X-Token"
        ]);

        $next = function (ServerRequest $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
    }

    public function testShouldReturn200WithTokenFromHeaderWithCustomRegexp()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET")
            ->withHeader("X-Token", self::$token);

        $response = new Response;

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "header" => "X-Token",
            "regexp" => "/(.*)/"
        ]);

        $next = function (ServerRequest $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
    }

    public function testShouldReturn200WithTokenFromCookie()
    {
        $request = ServerRequestFactory::fromGlobals(
            null,
            null,
            null,
            ["nekot" => self::$token],
            null
        );
        $request = $request
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "cookie" => "nekot",
        ]);

        $next = function (ServerRequest $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
    }

    public function testShouldReturn401WithFalseFromAfter()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $response = new Response;

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "after" => function ($request, $response, $arguments) {
                return $response
                    ->withBody(new Stream("php://memory"))
                    ->withStatus(401);
            }
        ]);

        $next = function (ServerRequest $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldAlterResponseWithAfter()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $response = new Response;

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "after" => function ($request, $response, $arguments) {
                return $response->withHeader("X-Brawndo", "plants crave");
            }
        ]);

        $next = function (ServerRequest $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("plants crave", (string) $response->getHeaderLine("X-Brawndo"));
    }

    public function testShouldReturn200WithOptions()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("OPTIONS");

        $response = new Response;

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit"
        ]);

        $next = function (ServerRequest $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
    }

    public function testShouldReturn400WithInvalidToken()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET")
            ->withHeader("Authorization", "Bearer invalid" . self::$token);

        $response = new Response;

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit"
        ]);

        $next = function (ServerRequest $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldReturn200WithoutTokenWithPath()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/public"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new BrancaAuthentication([
            "path" => ["/api", "/foo"],
            "secret" => "supersecretkeyyoushouldnotcommit"
        ]);

        $next = function (ServerRequest $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
    }

    public function testShouldReturn200WithoutTokenWithIgnore()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/api/ping"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new BrancaAuthentication([
            "path" => ["/api", "/foo"],
            "ignore" => ["/api/ping"],
            "secret" => "supersecretkeyyoushouldnotcommit"
        ]);

        $next = function (ServerRequest $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
    }

    public function testShouldNotAllowInsecure()
    {
        $this->setExpectedException("RuntimeException");

        $request = (new ServerRequest)
            ->withUri(new Uri("http://example.com/api"))
            ->withMethod("GET")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $response = new Response;

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit"
        ]);

        $next = function (ServerRequest $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);
    }

    public function testShoulAllowInsecure()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("http://example.com/api"))
            ->withMethod("GET")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $response = new Response;

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "secure" => false
        ]);

        $next = function (ServerRequest $request, Response $response) {
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
        $request = (new ServerRequest)
            ->withUri(new Uri("http://localhost/api"))
            ->withMethod("GET")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $response = new Response;

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit"
        ]);

        $next = function (ServerRequest $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
    }

    public function testShouldRelaxInsecureInExampleCom()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("http://example.com/api"))
            ->withMethod("GET")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $response = new Response;

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "relaxed" => ["example.com"],
        ]);

        $next = function (ServerRequest $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Foo", $response->getBody());
    }

    public function testShouldCallAfter()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $response = new Response;

        $dummy = null;
        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "after" => function ($request, $response, $arguments) use (&$dummy) {
                $dummy = $arguments["decoded"];
            }
        ]);

        $next = function (ServerRequest $request, Response $response) {
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
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");

        $response = new Response;

        $dummy = null;
        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "error" => function ($request, $response, $arguments) use (&$dummy) {
                $dummy = true;
            }
        ]);

        $next = function (ServerRequest $request, Response $response) {
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
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");

        $response = new Response;

        $dummy = null;
        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "error" => function ($request, $response, $arguments) use (&$dummy) {
                $dummy = true;
                $response->getBody()->write("Error");
                return $response;
            }
        ]);

        $next = function (ServerRequest $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("Error", $response->getBody());
        $this->assertTrue($dummy);
    }

    public function testShouldLog()
    {
        $logger = new \Psr\Log\NullLogger;
        $auth = new \Tuupola\Middleware\BrancaAuthentication([
            "logger" => $logger
        ]);
        $this->assertNull($auth->log(\Psr\Log\LogLevel::WARNING, "Token not found"));
    }

    public function testShouldAllowUnauthenticatedHttp()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("http://example.com/public/foo"))
            ->withMethod("GET");

        $response = new Response;

        $auth = new \Tuupola\Middleware\BrancaAuthentication([
            "path" => ["/api", "/bar"],
            "secret" => "supersecretkeyyoushouldnotcommit"
        ]);

        $next = function (ServerRequest $request, Response $response) {
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn401FromAfter()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $response = new Response;

        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "after" => function ($request, $response, $arguments) {
                return $response
                    ->withBody(new Stream("php://memory"))
                    ->withStatus(401);
            }
        ]);

        $next = function (ServerRequest $request, Response $response) {
            $response->getBody()->write("Foo");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldModifyRequestUsingBefore()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $response = new Response;

        $dummy = null;
        $auth = new BrancaAuthentication([
            "secret" => "supersecretkeyyoushouldnotcommit",
            "before" => function ($request, $response, $arguments) {
                return $request->withAttribute("test", "test");
            }
        ]);

        $next = function (ServerRequest $request, Response $response) {
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
        if (!class_exists("Equip\Dispatch\MiddlewareCollection")) {
            $this->markTestSkipped(
                "MiddlewareCollection class is not available."
            );
        }


        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");
        $response = new Response;

        $auth =

        $default = function (Request $request) {
            $response = new Response;
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
