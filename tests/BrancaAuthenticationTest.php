<?php

declare(strict_types=1);

/*

Copyright (c) 2017-2019 Mika Tuupola

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

/**
 * @see       https://branca.io/
 * @see       https://github.com/tuupola/branca-middleware
 * @see       https://github.com/tuupola/branca-php
 * @see       https://github.com/tuupola/branca-spec
 * @license   https://www.opensource.org/licenses/mit-license.php
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
use Tuupola\Middleware\BrancaAuthentication\RequestMethodRule;
use Tuupola\Middleware\BrancaAuthentication\RequestPathRule;
use Tuupola\Middleware\BrancaAuthentication\TestRule;

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

    public function testShouldReturn200WithTokenFromHeader()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api")
            ->withHeader("X-Token", "Bearer " . self::$token);

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "header" => "X-Token"
            ])
        ]);

        $response = $collection->dispatch($request, $default);
        var_dump($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn200WithTokenFromHeaderWithCustomRegexp()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api")
            ->withHeader("X-Token", self::$token);

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "header" => "X-Token",
                "regexp" => "/(.*)/"
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn200WithTokenFromCookie()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api")
            ->withCookieParams(["nekot" => self::$token]);

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "cookie" => "nekot"
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldUseCookieIfHeaderMissing()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api")
            ->withCookieParams(["token" => self::$token]);

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "header" => "X-Token",
                "regexp" => "/(.*)/",
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldAlterResponseWithAfter()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "after" => function ($response, $arguments) {
                    return $response->withHeader("X-Brawndo", "plants crave");
                }
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("plants crave", (string) $response->getHeaderLine("X-Brawndo"));
    }

    public function testShouldReturn200WithOptions()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api")
            ->withMethod("OPTIONS");

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

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn400WithInvalidToken()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api")
            ->withHeader("Authorization", "Bearer invalid" . self::$token);

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

    public function testShouldReturn200WithoutTokenWithPath()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/public");

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "path" => ["/api", "/foo"],
                "secret" => "supersecretkeyyoushouldnotcommit"
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn200WithoutTokenWithIgnore()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api/ping");

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "path" => ["/api", "/foo"],
                "ignore" => ["/api/ping"],
                "secret" => "supersecretkeyyoushouldnotcommit"
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldNotAllowInsecure()
    {
        $this->expectException("RuntimeException");

        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "http://example.com/api")
            ->withHeader("Authorization", "Bearer " . self::$token);

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
    }

    public function testShoulAllowInsecure()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "http://example.com/api")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "secure" => false
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldRelaxInsecureInLocalhost()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "http://localhost/api")
            ->withHeader("Authorization", "Bearer " . self::$token);

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

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldRelaxInsecureInExampleCom()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "http://example.com/api")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "relaxed" => ["example.com"],
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldCallAfter()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $dummy = null;

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "after" => function ($response, $arguments) use (&$dummy) {
                    $dummy = $arguments["decoded"];
                }
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
        $this->assertEquals(self::$token_as_array, json_decode($dummy, true));
    }

    public function testShouldCallAnonymousErrorFunction()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api");

        $dummy = null;

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "error" => function ($response, $arguments) use (&$dummy) {
                    $dummy = true;
                }
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
        $this->assertTrue($dummy);
    }

    public function testShouldCallInvokableErrorClass()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api");

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "error" => new TestErrorHandler
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(TestErrorHandler::class, $response->getBody());
    }

    public function testShouldCallArrayNotationError()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api");

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "error" => [TestErrorHandler::class, "error"]
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(TestErrorHandler::class, $response->getBody());
    }

    public function testShouldAllowUnauthenticatedHttp()
    {

        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/public/foo");

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "path" => ["/api", "/bar"],
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldReturn401FromAfter()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "after" => function ($response, $arguments) {
                    return $response
                        ->withBody((new StreamFactory)->createStream())
                        ->withStatus(401);
                }
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldModifyRequestUsingBefore()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $test = $request->getAttribute("test");
            $response->getBody()->write($test);
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "before" => function ($request, $arguments) {
                    return $request->withAttribute("test", "test");
                }
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("test", (string) $response->getBody());
    }

    public function testShouldHandleDefaultPath()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api");
        $default = function (ServerRequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };
        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "ignore" => "/api/login",
            ])
        ]);
        $response = $collection->dispatch($request, $default);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api/login");
        $response = $collection->dispatch($request, $default);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldBindToMiddleware()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/")
            ->withHeader("Authorization", "Bearer " . self::$token);

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $before = $request->getAttribute("before");
            $response->getBody()->write($before);
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "before" => function ($request, $arguments) {
                    $before = get_class($this);
                    return $request->withAttribute("before", $before);
                },
                "after" => function ($response, $arguments) {
                    $after = get_class($this);
                    $response->getBody()->write($after);
                    return $response;
                }

            ])
        ]);

        $response = $collection->dispatch($request, $default);
        $expected = str_repeat("Tuupola\Middleware\BrancaAuthentication", 2);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expected, (string) $response->getBody());
    }

    public function testShouldHandlePsr7()
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
            $response->getBody()->write("Success");
            return $response;
        };

        $response = $auth($request, $response, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }


    public function testShouldHandleInvokableRules()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api");

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "rules" => [
                    new RequestPathRule([
                        "path" => ["/api"],
                        "ignore" => ["/api/login"],
                    ]),
                    new RequestMethodRule([
                        "ignore" => ["OPTIONS"],
                    ])
                ],
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());

        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api/login");

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());
    }

    public function testShouldHandleArrayNotationRules()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api");

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "rules" => [
                    [TestRule::class, "false"]
                ],
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "rules" => [
                    [TestRule::class, "true"]
                ],
            ])
        ]);

        $response = $collection->dispatch($request, $default);
        $response = $collection->dispatch($request, $default);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testShouldHandleAnonymousFunctionRules()
    {
        $request = (new ServerRequestFactory)
            ->createServerRequest("GET", "https://example.com/api");

        $default = function (RequestInterface $request) {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write("Success");
            return $response;
        };

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "rules" => [
                    function ($request) {
                        return false;
                    }
                ],
            ])
        ]);

        $response = $collection->dispatch($request, $default);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Success", $response->getBody());

        $collection = new MiddlewareCollection([
            new BrancaAuthentication([
                "secret" => "supersecretkeyyoushouldnotcommit",
                "rules" => [
                    function ($request) {
                        return true;
                    }
                ],
            ])
        ]);

        $response = $collection->dispatch($request, $default);
        $response = $collection->dispatch($request, $default);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }
}
