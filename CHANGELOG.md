# Release Notes for the Polyel Framework

## [v0.5.1 (2021-01-13)](https://github.com/Superbition/Polyel-Framework/releases/tag/v0.5.1)

### Fixed

- Fix fatal server error for when no service suppliers are registered, caused the bind and singleton
class properties to be accessed when they are not initialised yet

## [v0.5.0 (2021-01-13)](https://github.com/Superbition/Polyel-Framework/releases/tag/v0.5.0)

### Added

- Show the Polyel framework version on the console when the server has started
- New console command `flush:session` which deletes all sessions based on the selected session driver
- Add support for container services, the framework can now manage service binds and singletons. By registering a
service supplier, more complicated services/classes can be formed. Support for deferred singleton services and global
server wide services. Visit the online documentation for more info: https://polyel.io/docs/using_polyel/services/
- The service container can now accept loadable objects from another container, allowing services to be shared between containers

### Changed

- Some wording on the console output after the server has started
- Moved the loading logic of application files like `controllers` or `middleware` etc. into an application loader class,
makes way for more updates later on to improve application and framework file loading

### Fixed

- Fixed incorrect/broken directory path for `create:command`
- Fixed incorrect/broken directory path for `create:controller`
- Fixed incorrect/broken directory path for `create:element`
- Fixed incorrect/broken directory path for `create:middleware`

## [v0.4.1 (2020-12-18)](https://github.com/Superbition/Polyel-Framework/releases/tag/v0.4.1)

### Fixed

- Fix invalid version constraint format for ext-swoole in the `composer.json` file

## [v0.4.0 (2020-12-18)](https://github.com/Superbition/Polyel-Framework/releases/tag/v0.4.0)

### Added

- Polyel now stores the generated CSRF token in a cookie so that JavaScript can use it to make valid
HTTP requests. The previous recommended way was to use a HTML meta tag, which is still possible but 
having a cookie makes this process much easier
- Added note on the README that Polyel is following SemVar versioning
- Composer requirement for Swoole version `^4.*`

### Changed

- The method `createCsrfToken` from the Session class will now return the newly generated CSRF token if one has not
already been created, if a token has already been created, `false` is returned instead.

### Fixed

- Incorrect PHPDoc method removed from the Route Facade, it had a method called `prepend` which is not
available in that class

## [v0.3.0 (2020-12-05)](https://github.com/Superbition/Polyel-Framework/releases/tag/v0.3.0)

### Added

- Polyel ASCII banner at server startup, moved from the Polyel skeleton, making it easier to update version information

### Changed

- Change the Polyel session cookie setting `SameSite` value to `Strict` from `None`. By using `None` it meant cookies are not
sent if HTTPS is not enabled, which isn't always the case during local development. So using `Strict` is a more
sane default

## [v0.2.1 (2020-12-03)](https://github.com/Superbition/Polyel-Framework/releases/tag/v0.2.1)

### Fixed

- Missing Polyel const version number, was set to `0.0.0` which didn't reflect the installed version

## [v0.2.0 (2020-12-03)](https://github.com/Superbition/Polyel-Framework/releases/tag/v0.2.0)

### Added

- ext-json to composer.json
- ext-mbstring to composer.json
- ext-openssl to composer.json

### Changed

- Updated the framework to match the directory paths for the Polyel Skeleton when installed via Composer
- Changed the constant ROOT_DIR to APP_DIR for better naming considering its move to the APP skeleton

## [v0.1.0 (2020-12-03)](https://github.com/Superbition/Polyel-Framework/releases/tag/v0.1.0)

- Initial release of the Polyel Framework :rocket:
