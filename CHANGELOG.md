# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.9] - 2022-12-29

### Commits

- Fixed "PHP Fatal error:  Uncaught TypeError: property_exists(): Argument #2 ($property) must be of type string, stdClass given". ([40c4164](https://github.com/pronamic/wp-deployer/commit/40c4164d079d8115e2aefea5bd9c9745ecdafa7e))

Full set of changes: [`1.2.8...1.2.9`][1.2.9]

[1.2.9]: https://github.com/pronamic/wp-deployer/compare/v1.2.8...v1.2.9

## [1.2.8] - 2022-12-29

### Commits

- Fixed error `stat ./build/*.zip: no such file or directory`. ([839125d](https://github.com/pronamic/wp-deployer/commit/839125d6b6264f77905db0784895e9f924b9a0e0))

Full set of changes: [`1.2.7...1.2.8`][1.2.8]

[1.2.8]: https://github.com/pronamic/wp-deployer/compare/v1.2.7...v1.2.8

## [1.2.7] - 2022-12-29

### Commits

- Added build archive as release asset. ([177c421](https://github.com/pronamic/wp-deployer/commit/177c421046d057d7429e7287783b545c97727e79))
- Added method to get all changelog entries. ([ac5331e](https://github.com/pronamic/wp-deployer/commit/ac5331ea16d00ebc4433fd6329b51ff89ce29751))
- Run Composer `preversion`, `version` and `postversion` scripts if defined. ([44c2ef3](https://github.com/pronamic/wp-deployer/commit/44c2ef363e96f62fd1006e2090d5c0458b403337))

Full set of changes: [`1.2.6...1.2.7`][1.2.7]

[1.2.7]: https://github.com/pronamic/wp-deployer/compare/v1.2.6...v1.2.7

## [1.2.6] - 2022-12-23

### Commits

- Use packages version as tagname in GitHub release tag link. ([824bd57](https://github.com/pronamic/wp-deployer/commit/824bd57cd7cd079212c96535fef3fb5dda7dd703))

Full set of changes: [`1.2.5...1.2.6`][1.2.6]

[1.2.6]: https://github.com/pronamic/wp-deployer/compare/v1.2.5...v1.2.6

## [1.2.5] - 2022-12-23

### Commits

- Updated VersionCommand.php ([93678ca](https://github.com/pronamic/wp-deployer/commit/93678cabe0b43dd2103b9a649faeb44c199a054c))

Full set of changes: [`1.2.4...1.2.5`][1.2.5]

[1.2.5]: https://github.com/pronamic/wp-deployer/compare/v1.2.4...v1.2.5

## [1.2.4] - 2022-12-23

### Commits

- Special `nightly` tagname does not require `v` prefix. ([e0f9f00](https://github.com/pronamic/wp-deployer/commit/e0f9f009f121bef480738f0e3205cb9e2e6f0786))

Full set of changes: [`1.2.3...1.2.4`][1.2.4]

[1.2.4]: https://github.com/pronamic/wp-deployer/compare/v1.2.3...v1.2.4

## [1.2.3] - 2022-12-23
### Fixed

- Fixed Composer changes GitHub release notes link.

Full set of changes: [`1.2.2...1.2.3`][1.2.3]

[1.2.3]: https://github.com/pronamic/wp-deployer/compare/v1.2.2...v1.2.3

## [1.2.2] - 2022-12-23

### Composer

- Changed `symfony/console` from `^6.0 || ^6.1 || ^6.2` to `v6.0.16`.
	Release notes: https://github.com/symfony/console/releases/tag/v6.0.16
- Changed `symfony/filesystem` from `^6.0 || ^6.1 || ^6.2` to `v6.0.13`.
	Release notes: https://github.com/symfony/filesystem/releases/tag/v6.0.13
- Changed `symfony/process` from `^6.0 || ^6.1 || ^6.2` to `v6.0.11`.
	Release notes: https://github.com/symfony/process/releases/tag/v6.0.11

Full set of changes: [`1.2.1...1.2.2`][1.2.2]

[1.2.2]: https://github.com/pronamic/wp-deployer/compare/v1.2.1...v1.2.2

## [1.2.1] - 2022-12-22

### Commits

- Added Composer heading to changelog. ([0eb183d](https://github.com/pronamic/wp-deployer/commit/0eb183d6f6ce26415331fcb75caecc2af78fcf12))
- Fixed GitHub release URL. ([34fc8c2](https://github.com/pronamic/wp-deployer/commit/34fc8c2c56bae2295310fb52a82f6b8b7a664daf))

Full set of changes: [`1.2.0...1.2.1`][1.2.1]

[1.2.1]: https://github.com/pronamic/wp-deployer/compare/v1.2.0...v1.2.1

## [1.2.0] - 2022-12-22
- Improved detection of Composer changes.

Full set of changes: [`1.1.2...1.2.0`][1.2.0]

[1.2.0]: https://github.com/pronamic/wp-deployer/compare/v1.1.2...v1.2.0

## [1.1.2] - 2022-12-20
### Fixed

- Changed `symfony` dependencies to `^6.0 || ^6.1 || ^6.2` for PHP `8.0` support.

Full set of changes: [`1.1.1...1.1.2`][1.1.2]

[1.1.2]: https://github.com/pronamic/wp-deployer/compare/v1.1.1...v1.1.2

## [1.1.1] - 2022-12-20
### Fixed

- Prefix tagnames in compare links with `v`.
Full set of changes: [`1.1.0...1.1.1`][1.1.1]

[1.1.1]: https://github.com/pronamic/wp-deployer/compare/v1.1.0...v1.1.1

## [1.1.0] - 2022-12-20
### Added

- The `wp-deployer version` command now checks for outdated Composer packages. If there are any outdated packages, the user will have to confirm their use.

Full set of changes: [`1.0.0...1.1.0`][1.1.0]

[1.1.0]: https://github.com/pronamic/wp-deployer/compare/v1.0.0...v1.1.0

## [1.0.0] - 2022-12-20

- First release.

[unreleased]: https://github.com/pronamic/wp-deployer/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/pronamic/wp-deployer/releases/tag/v1.0.0
