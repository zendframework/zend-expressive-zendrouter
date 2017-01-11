# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.1.0 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.0.1 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.0.0 - 2017-01-11

### Added

- [#16](https://github.com/zendframework/zend-expressive-zendrouter/pull/16)
  adds support for zend-expressive-router 2.0. This includes a breaking change
  to those _extending_ `Zend\Expressive\Router\ZendRouter`, as the
  `generateUri()` method now expects a third, optional argument,
  `array $options = []`.

  For consumers, this represents new functionality; you may now pass router
  options, such as a translator and/or translation text domain, via the new
  argument when generating a URI.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.3.0 - 2016-12-14

### Added

- Nothing.

### Changed

- [#12](https://github.com/zendframework/zend-expressive-zendrouter/pull/12)
  updates the zend-expressive-router dependency to 1.3.2+

- [#12](https://github.com/zendframework/zend-expressive-zendrouter/pull/12)
  updates the router to compose the `Zend\Expressive\Router\Route` instance
  associated with a successful route match in the returned `RouteResult`. This
  allows you to access other route metadata like the path, allowed HTTP methods,
  and route options.

- [#12](https://github.com/zendframework/zend-expressive-zendrouter/pull/12)
  updates the router to always support `HEAD` and `OPTIONS` requests made to any
  valid route. Dispatchers will need to check if such requests are supported
  explicitly or implicitly by the matched route (using `Route::implicitHead()`
  and `Route::implicitOptions()`).

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.2.0 - 2016-08-11

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- This release removes support for PHP 5.5.

### Fixed

- [#7](https://github.com/zendframework/zend-expressive-zendrouter/pull/7)
  updates the zend-router dependency to `^3.0`; this also required changing
  which routes and routers are imported internally to use the new namespace
  introduced in that version. The changes should have no effect on existing
  code, except that they will result in dependency updates.

## 1.1.0 - 2016-03-09

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#6](https://github.com/zendframework/zend-expressive-zendrouter/pull/6)
  updates the component to depend on zend-router instead of zend-mvc.

## 1.0.1 - 2016-01-04

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#3](https://github.com/zendframework/zend-expressive-zendrouter/pull/3) fixes
  an issue whereby appending a trailing slash to a route that did not define one
  was resulting in a 405 instead of a 404 error.

## 1.0.0 - 2015-12-07

First stable release.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.3.0 - 2015-12-02

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updated to use [zendframework/zend-expressive-router](https://github.com/zendframework/zend-expressive-router)
  instead of zendframework/zend-expressive.

## 0.2.0 - 2015-10-20

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updated to zend-expressive RC1.
- Added branch alias for dev-master, pointing to 1.0-dev.

## 0.1.0 - 2015-10-10

Initial release.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
