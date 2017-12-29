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
use Tuupola\Middleware\BrancaAuthentication\RequestMethodRule;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;

class RequestMethodRuleTest extends TestCase
{

    public function testShouldNotAuthenticateOptions()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("OPTIONS");

        $response = new Response;
        $rule = new RequestMethodRule;

        $this->assertFalse($rule($request));
    }

    public function testShouldAuthenticatePost()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("POST");

        $rule = new RequestMethodRule;

        $this->assertTrue($rule($request));
    }

    public function testShouldAuthenticateGet()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");

        $rule = new RequestMethodRule;

        $this->assertTrue($rule($request));
    }

    public function testShouldConfigureIgnore()
    {
        $request = (new ServerRequest)
            ->withUri(new Uri("https://example.com/api"))
            ->withMethod("GET");

        $rule = new RequestMethodRule([
            "ignore" => ["GET"]
        ]);

        $this->assertFalse($rule($request));
    }
}
