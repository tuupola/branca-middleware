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

use Branca\Branca;
use Closure;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuupola\Http\Factory\ResponseFactory;
use Tuupola\Middleware\DoublePassTrait;
use Tuupola\Middleware\BrancaAuthentication\CallableHandler;
use Tuupola\Middleware\BrancaAuthentication\RequestMethodRule;
use Tuupola\Middleware\BrancaAuthentication\RequestPathRule;

final class BrancaAuthentication implements MiddlewareInterface
{
    use DoublePassTrait;

    /**
     * PSR-3 compliant logger.
     */
    private $logger;

    /**
     * Last error message.
     */
    private $message;

    /**
     * The rules stack.
     */
    public $rules;

    /**
     * Stores all the options passed to the rule
     */
    private $options = [
        "ttl" => null,
        "secure" => true,
        "relaxed" => ["localhost", "127.0.0.1"],
        "header" => "Authorization",
        "regexp" => "/Bearer\s+(.*)$/i",
        "cookie" => "token",
        "attribute" => "token",
        "path" => null,
        "ignore" => null,
        "before" => null,
        "after" => null,
        "error" => null
    ];

    public function __construct(array $options = [])
    {
        /* Setup stack for rules */
        $this->rules = new \SplStack;

        /* Store passed in options overwriting any defaults. */
        $this->hydrate($options);

        /* If nothing was passed in options add default rules. */
        if (!isset($options["rules"])) {
            $this->rules->push(new RequestMethodRule([
                "ignore" => ["OPTIONS"]
            ]));
        }

        /* If path was given in easy mode add rule for it. */
        if (null !== ($this->options["path"])) {
            $this->rules->push(new RequestPathRule([
                "path" => $this->options["path"],
                "ignore" => $this->options["ignore"]
            ]));
        }
    }

    /**
     * Process a request in PSR-15 style and return a response
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $scheme = $request->getUri()->getScheme();
        $host = $request->getUri()->getHost();

        /* If rules say we should not authenticate call next and return. */
        if (false === $this->shouldAuthenticate($request)) {
            return $handler->handle($request);
        }

        /* HTTP allowed only if secure is false or server is in relaxed array. */
        if ("https" !== $scheme && true === $this->options["secure"]) {
            if (!in_array($host, $this->options["relaxed"])) {
                $message = sprintf(
                    "Insecure use of middleware over %s denied by configuration.",
                    strtoupper($scheme)
                );
                throw new \RuntimeException($message);
            }
        }

        /* If token cannot be found return with 401 Unauthorized. */
        if (null === $token = $this->fetchToken($request)) {
            $response = (new ResponseFactory)->createResponse(401);
            return $this->processError($response, [
                "message" => $this->message
            ]);
        }

        /* If token cannot be decoded return with 401 Unauthorized. */
        if (null === $decoded = $this->decodeToken($token)) {
            $response = (new ResponseFactory)->createResponse(401);
            return $this->processError($response, [
                "message" => $this->message,
                "token" => $token
            ]);
        }

        /* Add decoded token to request as attribute when requested. */
        $params = ["decoded" => $decoded];
        if ($this->options["attribute"]) {
            $request = $request->withAttribute($this->options["attribute"], $decoded);
        }

        /* Modify $request before calling next middleware. */
        $request = $this->processBefore($request, $params);

        /* Everything ok, call next middleware. */
        $response = $handler->handle($request);

        /* Modify $response before returning. */
        $response = $this->processAfter($response, $params);

        return $response;
    }

    /**
     * Set all rules in the stack. This method is immutable.
     */
    public function withRules(array $rules): self
    {
        $new = clone $this;
        /* Clear the stack */
        unset($new->rules);
        $new->rules = new \SplStack;
        /* Add the rules */
        foreach ($rules as $callable) {
            $new = $new->addRule($callable);
        }
        var_dump($new->rules);
        return $new;
    }

    /**
     * Add a rule to the stack. This method is immutable.
     */
    public function addRule(callable $callable): self
    {
        $new = clone $this;
        $new->rules = clone $this->rules;
        $new->rules->push($callable);
        return $new;
    }

    /**
     * Check if middleware should authenticate
     */
    private function shouldAuthenticate(ServerRequestInterface $request): bool
    {
        /* If any of the rules in stack return false will not authenticate */
        foreach ($this->rules as $callable) {
            if (false === $callable($request)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Modify the request before calling next middleware.
     */
    private function processBefore(
        ServerRequestInterface $request,
        array $arguments
    ): ServerRequestInterface {
        if (is_callable($this->options["before"])) {
            $beforeRequest = $this->options["before"]($request, $arguments);
            if ($beforeRequest instanceof ServerRequestInterface) {
                $request = $beforeRequest;
            }
        }
        return $request;
    }

    /**
     * Modify the response before returning from the middleware.
     */
    private function processAfter(
        ResponseInterface $response,
        array $arguments
    ): ResponseInterface {
        if (is_callable($this->options["after"])) {
            $afterResponse = $this->options["after"]($response, $arguments);
            if ($afterResponse instanceof ResponseInterface) {
                $response = $afterResponse;
            }
        }
        return $response;
    }

    /**
     * Call the error handler if it exists
     */
    private function processError(
        ResponseInterface $response,
        array $arguments
    ): ResponseInterface {
        if (is_callable($this->options["error"])) {
            $handlerResponse = $this->options["error"]($response, $arguments);
            if ($handlerResponse instanceof ResponseInterface) {
                return $handlerResponse;
            }
        }
        return $response;
    }

    /**
     * Fetch the access token
     */
    private function fetchToken(ServerRequestInterface $request): ?string
    {
        $header = "";
        $message = "Using token from request header";

        /* Check for token in header. */
        $headers = $request->getHeader($this->options["header"]);
        $header = isset($headers[0]) ? $headers[0] : "";

        if (preg_match($this->options["regexp"], $header, $matches)) {
            $this->log(LogLevel::DEBUG, $message);
            return $matches[1];
        }

        /* Token not found in header try a cookie. */
        $cookieParams = $request->getCookieParams();

        if (isset($cookieParams[$this->options["cookie"]])) {
            $this->log(LogLevel::DEBUG, "Using token from cookie");
            $this->log(LogLevel::DEBUG, $cookieParams[$this->options["cookie"]]);
            return $cookieParams[$this->options["cookie"]];
        };

        /* If everything fails log and return false. */
        $this->message = "Token not found";
        $this->log(LogLevel::WARNING, $this->message);
        return null;
    }

    /**
     * Decode the token
     */
    private function decodeToken(?string $token): ?string
    {
        try {
            $branca = new Branca($this->options["secret"]);
            return $branca->decode($token, $this->options["ttl"]);
        } catch (\Exception $exception) {
            $this->message = $exception->getMessage();
            $this->log(LogLevel::WARNING, $exception->getMessage(), [$token]);
            return null;
        }
    }

    /**
     * Hydrate options from given array
     */
    private function hydrate(array $data = []): void
    {
        foreach ($data as $key => $value) {
            /* https://github.com/facebook/hhvm/issues/6368 */
            $key = str_replace(".", " ", $key);
            $method = lcfirst(ucwords($key));
            $method = str_replace(" ", "", $method);
            if (method_exists($this, $method)) {
                /* Try to use setter */
                call_user_func([$this, $method], $value);
            } else {
                /* Or fallback to setting option directly */
                $this->options[$key] = $value;
            }
        }
    }

    /**
     * Set the optional TTL how long token is considered valid
     */
    private function ttl(int $ttl): void
    {
        $this->options["ttl"] = $ttl;
    }

    /**
     * Set path where middleware should be binded to
     */
    private function path($path): void
    {
        $this->options["path"] = $path;
    }

    /**
     * Set path which middleware ignores
     */
    private function ignore($ignore): void
    {
        $this->options["ignore"] = $ignore;
    }

    /**
     * Set the cookie name where to search the token from
     */
    private function cookie(string $cookie): void
    {
        $this->options["cookie"] = $cookie;
    }

    /**
     * Set the secure flag
     */
    private function secure(bool $secure): void
    {
        $this->options["secure"] = $secure;
    }

    /**
     * Set hosts where secure rule is relaxed
     */
    private function relaxed(array $relaxed): void
    {
        $this->options["relaxed"] = $relaxed;
    }

    /**
     * Set the secret key
     */
    private function secret(string $secret): void
    {
        $this->options["secret"] = $secret;
    }

    /**
     * Set the error handler
     */
    private function error(Closure $error): void
    {
        $this->options["error"] = $error->bindTo($this);
    }

    /**
     * Set the PSR-3 logger.
     */
    private function logger(LoggerInterface $logger = null): void
    {
        $this->logger = $logger;
    }

    /**
     * Logs with an arbitrary level.
     */
    private function log($level, string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }

    /**
     * Set the header where token is searched from,
     */
    private function header(string $header): void
    {
        $this->options["header"] = $header;
    }

    /**
     * Set the regexp used to extract token from the header.
     */
    private function regexp(string $regexp): void
    {
        $this->options["regexp"] = $regexp;
    }

    /**
     * Set the before handler.
     */

    private function before(Closure $before)
    {
        $this->options["before"] = $before->bindTo($this);
    }

    /**
     * Set the after handler.
     */
    private function after(Closure $after): void
    {
        $this->options["after"] = $after->bindTo($this);
    }

    /**
     * Set the rules
     */
    private function rules(array $rules): void
    {
        $this->rules = $rules;
    }
}
