# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## [0.4.0-dev](https://github.com/tuupola/server-timing-middleware/compare/0.3.1...master) - unreleased
### Added
- Support for the [approved version of PSR-15](https://github.com/php-fig/http-server-middleware).

## [0.3.1](https://github.com/tuupola/branca-middleware/compare/0.3.0...0.3.1) -  2017-12-29
### Fixed
- Moved `overtrue/phplint` to dev dependencies where it belongs.

## [0.3.0](https://github.com/tuupola/branca-middleware/compare/0.2.0...0.3.0) -  2017-12-29
### Changed
- PHP 7.1 is now minimal requirement.
- PSR-7 double pass is now supported via [tuupola/callable-handler](https://github.com/tuupola/callable-handler) library.
- Error callback now receives only response and arguments, request was removed.
- Before callback now receives only requesr and arguments, response was removed.
- After callback now receives only response and arguments, request was removed.
- Tests are now run as PSR-15 middleware.

## [0.2.0](https://github.com/tuupola/branca-middleware/compare/0.1.0...0.2.0) -  2017-12-06
### Added
- Support for the [latest version of PSR-15](https://github.com/http-interop/http-server-middleware).

### Changed
-  PHP 7.0 is now minimal requirement.

## 0.1.0 - 2017-08-08
Initial realese. Supports both PSR-7 and PSR-15 style middlewares. Both have unit tests. However PSR-15 has not really been tested in production.
