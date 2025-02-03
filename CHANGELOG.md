# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased] - TBD

## [1.6.2] - 2025-02-03
### Changed
- Bump WordPress "tested up to" version 6.7 (props [@thrijith](https://github.com/thrijith), [@jeffpaul](https://github.com/jeffpaul), [@Sidsector9](https://github.com/Sidsector9) via [#74](https://github.com/10up/block-catalog/pull/74), [#75](https://github.com/10up/block-catalog/pull/75)).

### Security
- Bump `webpack` from 5.91.0 to 5.94.0 (props [@dependabot](https://github.com/apps/dependabot), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#68](https://github.com/10up/block-catalog/pull/68)).
- Bump `serve-static` from 1.15.0 to 1.16.2 and `express` from 4.19.2 to 4.21.1 (props [@dependabot](https://github.com/apps/dependabot), [@Sidsector9](https://github.com/Sidsector9) via [#70](https://github.com/10up/block-catalog/pull/70)).
- Bump `cookie` from 0.6.0 to 0.7.1 (props [@dependabot](https://github.com/apps/dependabot), [@Sidsector9](https://github.com/Sidsector9) via [#76](https://github.com/10up/block-catalog/pull/76)).

### Developer
- Update deprecated step in the WordPress Playground blueprint file (props [@barryceelen](https://github.com/barryceelen), [@dkotter](https://github.com/dkotter) via [#67](https://github.com/10up/block-catalog/pull/67)).
- Add WordPress Playground badge, update other badges and add banner image to the README (props [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#66](https://github.com/10up/block-catalog/pull/66), [#69](https://github.com/10up/block-catalog/pull/69)).

## [1.6.1] - 2024-07-09
### Changed
- Update [Support Level](https://github.com/10up/block-catalog/blob/develop/README.md#support-level) from `Beta` to `Stable` (props [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#56](https://github.com/10up/block-catalog/pull/56)).
- Bump WordPress "tested up to" version 6.6 (props [@sudip-md](https://github.com/sudip-md), [@jeffpaul](https://github.com/jeffpaul) via [#60](https://github.com/10up/block-catalog/pull/60)).
- Bump WordPress minimum supported version to 6.4 (props [@sudip-md](https://github.com/sudip-md), [@jeffpaul](https://github.com/jeffpaul) via [#60](https://github.com/10up/block-catalog/pull/60)).

### Security
- Bump `braces` from 3.0.2 to 3.0.3 (props [@dependabot](https://github.com/apps/dependabot), [@faisal-alvi](https://github.com/faisal-alvi) via [#58](https://github.com/10up/block-catalog/pull/58)).
- Bump `ws` from 7.5.9 to 7.5.10 (props [@dependabot](https://github.com/apps/dependabot), [@faisal-alvi](https://github.com/faisal-alvi) via [#58](https://github.com/10up/block-catalog/pull/58)).

## [1.6.0] - 2024-05-13
### Added
- WP-CLI command, `export`, to generate a CSV of the block catalog (props [@dsawardekar](https://github.com/dsawardekar), [@psorensen](https://github.com/psorensen), [@Sidsector9](https://github.com/Sidsector9) via [#52](https://github.com/10up/block-catalog/pull/52)).
- Classic Editor block detection (props [@dsawardekar](https://github.com/dsawardekar), [@Sidsector9](https://github.com/Sidsector9) via [#53](https://github.com/10up/block-catalog/pull/53)).

### Changed
- Bump WordPress "tested up to" version 6.5 (props [@sudip-md](https://github.com/sudip-md), [@jeffpaul](https://github.com/jeffpaul) via [#51](https://github.com/10up/block-catalog/pull/51)).
- Bump WordPress minimum from 5.7 to 6.3 (props [@sudip-md](https://github.com/sudip-md), [@jeffpaul](https://github.com/jeffpaul) via [#51](https://github.com/10up/block-catalog/pull/51)).
- Replaced [lee-dohm/no-response](https://github.com/lee-dohm/no-response) with [actions/stale](https://github.com/actions/stale) to help with closing no-response/stale issues (props [@jeffpaul](https://github.com/jeffpaul) via [#48](https://github.com/10up/block-catalog/pull/48)).

### Security
- Bump `express` from 4.18.2 to 4.19.2 (props [@dependabot](https://github.com/apps/dependabot), [@Sidsector9](https://github.com/Sidsector9) via [#50](https://github.com/10up/block-catalog/pull/50)).
- Bump `follow-redirects` from 1.15.5 to 1.15.6 (props [@dependabot](https://github.com/apps/dependabot), [@Sidsector9](https://github.com/Sidsector9) via [#50](https://github.com/10up/block-catalog/pull/50)).
- Bump `postcss` from 7.0.39 to 8.4.33 (props [@dependabot](https://github.com/apps/dependabot), [@Sidsector9](https://github.com/Sidsector9) via [#50](https://github.com/10up/block-catalog/pull/50)).
- Bump `10up-toolkit` from 5.2.3 to 6.0.1 (props [@dependabot](https://github.com/apps/dependabot), [@Sidsector9](https://github.com/Sidsector9) via [#50](https://github.com/10up/block-catalog/pull/50)).
- Bump `webpack-dev-middleware` from 5.3.3 to 5.3.4 (props [@dependabot](https://github.com/apps/dependabot), [@Sidsector9](https://github.com/Sidsector9) via [#50](https://github.com/10up/block-catalog/pull/50)).

## [1.5.4] - 2024-02-29
### Added
- Support for the WordPress.org plugin preview (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#38](https://github.com/10up/block-catalog/pull/38)).

### Changed
- Significantly improved performance of block catalog reset on larger WordPress installations (props [@dsawardekar](https://github.com/dsawardekar), [@Sidsector9](https://github.com/Sidsector9) via [#41](https://github.com/10up/block-catalog/pull/41)).
- Clean up NPM dependencies and update the minimum node version to 20 (props [@Sidsector9](https://github.com/Sidsector9), [@dsawardekar](https://github.com/dsawardekar) via [#43](https://github.com/10up/block-catalog/pull/43)).

### Security
- Bump `tj-actions/changed-files` from 39 to 41 (props [@dependabot](https://github.com/apps/dependabot), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#39](https://github.com/10up/block-catalog/pull/39)).
- Bump `follow-redirects` from 1.15.2 to 1.15.4 (props [@dependabot](https://github.com/apps/dependabot), [@Sidsector9](https://github.com/Sidsector9) via [#40](https://github.com/10up/block-catalog/pull/40)).

## [1.5.3] - 2023-11-23
### Fixed
- PHP 8.2 deprecation warnings (props [@dsawardekar](https://github.com/dsawardekar), [@ravinderk](https://github.com/ravinderk) via [#34](https://github.com/10up/block-catalog/pull/34)).

### Added
- PHPUnit 9.x support (props [@dsawardekar](https://github.com/dsawardekar), [@ravinderk](https://github.com/ravinderk) via [#34](https://github.com/10up/block-catalog/pull/34)).

### Security
- Bump `sharp` from 0.32.3 to 0.32.6 (props [@dependabot](https://github.com/apps/dependabot), [@faisal-alvi](https://github.com/faisal-alvi) via [#32](https://github.com/10up/block-catalog/pull/32)).

## [1.5.2] - 2023-11-16
### Changed
- Bump WordPress "tested up to" version to 6.4 (props [@qasumitbagthariya](https://github.com/qasumitbagthariya), [@jeffpaul](https://github.com/jeffpaul) via [#28](https://github.com/10up/block-catalog/pull/28), [#29](https://github.com/10up/block-catalog/pull/29)).

## [1.5.1] - 2023-10-24
**Note that this release changes the name of the base plugin file. As such, you'll probably need to reactivate the plugin after updating.**

### Added
- Add our standard GitHub Action automations (props [@jeffpaul](https://github.com/jeffpaul), [@dsawardekar](https://github.com/dsawardekar), [@dkotter](https://github.com/dkotter) via [#10](https://github.com/10up/block-catalog/pull/10), [#20](https://github.com/10up/block-catalog/pull/20), [#22](https://github.com/10up/block-catalog/pull/22), [#23](https://github.com/10up/block-catalog/pull/23), [#24](https://github.com/10up/block-catalog/pull/24), [#25](https://github.com/10up/block-catalog/pull/25)).

### Changed
- Update our plugin image assets (props [Brooke Campbell](https://www.linkedin.com/in/brookecampbelldesign/), [@jeffpaul](https://github.com/jeffpaul), [@dsawardekar](https://github.com/dsawardekar), [@faisal-alvi](https://github.com/faisal-alvi) via [#11](https://github.com/10up/block-catalog/pull/11), [#17](https://github.com/10up/block-catalog/pull/17)).
- Updated the main plugin file name (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc), [@dsawardekar](https://github.com/dsawardekar) via [#18](https://github.com/10up/block-catalog/pull/18)).

### Security
- Bump `@babel/traverse` from 7.22.8 to 7.23.2 (props [@dependabot](https://github.com/apps/dependabot), [@dkotter](https://github.com/dkotter) via [#21](https://github.com/10up/block-catalog/pull/21)).

## [1.5.0] - 2023-08-11
### Added
- `Beta` Support Level (props [@jeffpaul](https://github.com/jeffpaul), [@dsawardekar](https://github.com/dsawardekar) via [#3](https://github.com/10up/block-catalog/pull/3)).
- Adds support for multisite via WP CLI (props [@dsawardekar](https://github.com/dsawardekar), [@Sidsector9](https://github.com/Sidsector9) via [#9](https://github.com/10up/block-catalog/pull/9)).

### Fixed
- Missing name in the `block_catalog_taxonomy_options` hook (props [@dsawardekar](https://github.com/dsawardekar), [@fabiankaegy](https://github.com/fabiankaegy) via [#6](https://github.com/10up/block-catalog/pull/6)).

## [1.4.0] - 2022-12-03
- Improves Core Block Display Titles logic
- Fixes parent term for blocks registered without namespace
- Improve Reusable Block detection
- Add hooks to support nested variations
- Adds unit tests

## [1.3.2] - 2022-11-25
- Updates readme.txt

## [1.3.1] - 2022-11-25
- Minor docs updates

## [1.3.0] - 2022-11-25
- Adds support for hierarchical classification
- Improves WP CLI find command
- Adds inline filter hook documentation
- Updates screenshots

## [1.2.2] - 2022-11-25
- Updates Documentation

## [1.2.1] - 2022-11-25
- Improves block title detection when default title is missing.
- Initial svn release

## [1.2.0] - 2022-11-24
- Improves filter output with wp_kses.

## [1.1.0] - 2022-11-23
- Improves batch indexing for larger sites.
- Refactor delete index to use batch mode.
- Improves error handling during indexing & deleting via WP-Admin.

## [1.0.1] - 2022-11-21
- Initial release

[Unreleased]: https://github.com/10up/block-catalog/compare/trunk...develop
[1.6.2]: https://github.com/10up/block-catalog/compare/1.6.1...1.6.2
[1.6.1]: https://github.com/10up/block-catalog/compare/1.6.0...1.6.1
[1.6.0]: https://github.com/10up/block-catalog/compare/1.5.4...1.6.0
[1.5.4]: https://github.com/10up/block-catalog/compare/1.5.3...1.5.4
[1.5.3]: https://github.com/10up/block-catalog/compare/1.5.2...1.5.3
[1.5.2]: https://github.com/10up/block-catalog/compare/1.5.1...1.5.2
[1.5.1]: https://github.com/10up/block-catalog/compare/1.5.0...1.5.1
[1.5.0]: https://github.com/10up/block-catalog/compare/1.4.0...1.5.0
[1.4.0]: https://github.com/10up/block-catalog/compare/1.3.2...1.4.0
[1.3.2]: https://github.com/10up/block-catalog/compare/1.3.1...1.3.2
[1.3.1]: https://github.com/10up/block-catalog/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/10up/block-catalog/compare/1.2.2...1.3.0
[1.2.2]: https://github.com/10up/block-catalog/compare/1.2.1...1.2.2
[1.2.1]: https://github.com/10up/block-catalog/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/10up/block-catalog/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/10up/block-catalog/compare/1.0.1...1.1.0
[1.0.1]: https://github.com/10up/block-catalog/tree/v1.0.1
