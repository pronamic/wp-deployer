# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
