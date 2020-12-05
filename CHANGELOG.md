# Release Notes for the Polyel Framework

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