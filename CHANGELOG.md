# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased] - TBD

## [1.5.3] - 2023-11-23

### Fixed

- PHP 8.2 deprecation warnings

### Added

PHPUnit 9.x support

### Security

Bumps sharp from 0.32.3 to 0.32.6

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
