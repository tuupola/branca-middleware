# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## [0.6.1](https://github.com/tuupola/branca-middleware/compare/0.6.0...master) - unreleased
### Fixed
- Bump minimum requirement of `tuupola/http-factory` to `1.0.2` . This is to avoid Composer 2 installing the broken `1.0.1` version which will also cause `psr/http-factory` to be removed. ([#20](https://github.com/tuupola/branca-middleware/pull/20))

### Removed
- Cookie contents from debug log ([#18](https://github.com/tuupola/branca-middleware/pull/18)).

## [0.6.0](https://github.com/tuupola/branca-middleware/compare/0.5.2...0.6.0) - 2019-04-11
### Added
- Error handler now receives also the request object as parameter ([#13](https://github.com/tuupola/branca-middleware/pull/13)).
  ```php
  $app->add(new Tuupola\Middleware\BrancaAuthentication([
      "secret" => "supersecretkeyyoushouldnotcommit",
      "error" => function ($request, $response, $arguments) {
        ...
      }
  ]));
  ```

### Changed
- Rules can now be a plain callable and they do not need to implement `RuleInterface` anymore ([#12](https://github.com/tuupola/branca-middleware/pull/12)).

### Fixed
- Callables for before, after and error handlers are not assumed to be instance of a `Closure` ([#16](https://github.com/tuupola/branca-middleware/pull/16)).
- Cookie was ignored if if using `/(.*)/` as regexp and the configured header was missing from request ([#17](https://github.com/tuupola/branca-middleware/pull/17)).

## [0.5.2](https://github.com/tuupola/branca-middleware/compare/0.5.1...0.5.2) - 2019-01-09
### Added
- Support for tuupola/branca:^1.0 and ^2.0

## [0.5.1](https://github.com/tuupola/branca-middleware/compare/0.5.0...0.5.1) - 2018-10-12
### Added
- Support for tuupola/callable-handler:^1.0 and tuupola/http-factory:^1.0

## [0.5.0](https://github.com/tuupola/branca-middleware/compare/0.4.1...0.5.0) - 2018-08-07
### Added
- Support for the stable version of PSR-17

## [0.4.1](https://github.com/tuupola/branca-middleware/compare/0.3.0...0.4.1) - 2018-04-05
### Fixed
- If rules were passed as an array to constructor they were ignored ([#9](https://github.com/tuupola/branca-middleware/pull/9)).

## [0.4.0](https://github.com/tuupola/branca-middleware/compare/0.3.1...0.4.0) - 2018-01-25
### Added
- Support for the [approved version of PSR-15](https://github.com/php-fig/http-server-middleware).

## [0.3.1](https://github.com/tuupola/branca-middleware/compare/0.3.0...0.3.1) - 2017-12-29
### Fixed
- Moved `overtrue/phplint` to dev dependencies where it belongs.

## [0.3.0](https://github.com/tuupola/branca-middleware/compare/0.2.0...0.3.0) - 2017-12-29
### Changed
- PHP 7.1 is now minimal requirement.
- PSR-7 double pass is now supported via [tuupola/callable-handler](https://github.com/tuupola/callable-handler) library.
- Error callback now receives only response and arguments, request was removed.
- Before callback now receives only requesr and arguments, response was removed.
- After callback now receives only response and arguments, request was removed.
- Tests are now run as PSR-15 middleware.

## [0.2.0](https://github.com/tuupola/branca-middleware/compare/0.1.0...0.2.0) - 2017-12-06
### Added
- Support for the [latest version of PSR-15](https://github.com/http-interop/http-server-middleware).

### Changed
-  PHP 7.0 is now minimal requirement.

## 0.1.0 - 2017-08-08
Initial realese. Supports both PSR-7 and PSR-15 style middlewares. Both have unit tests. However PSR-15 has not really been tested in production.
