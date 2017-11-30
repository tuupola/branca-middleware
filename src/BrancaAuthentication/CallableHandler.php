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

namespace Tuupola\Middleware\BrancaAuthentication;

use Interop\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CallableHandler implements RequestHandlerInterface
{
    private $callable;
    private $response;

    public function __construct(callable $callable, ResponseInterface $response)
    {
        $this->callable = $callable;
        $this->response = $response;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $callable = $this->callable;
        return $callable($request, $this->response);
    }
}
