# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## [0.3.0-dev](https://github.com/tuupola/branca-middleware/compare/0.3.0...master) -  Unreleased
### Changed
- PHP 7.1 is now minimal requirement.
- PSR-7 double pass is now supported via [tuupola/callable-handler](https://github.com/tuupola/callable-handler) library.


## [0.2.0](https://github.com/tuupola/branca-middleware/compare/0.1.0...0.2.0) -  2017-12-06
### Added
- Support for the [latest version of PSR-15](https://github.com/http-interop/http-server-middleware).

### Changed
-  PHP 7.0 is now minimal requirement.

## 0.1.0 - 2017-08-08
Initial realese. Supports both PSR-7 and PSR-15 style middlewares. Both have unit tests. However PSR-15 has not really been tested in production.
