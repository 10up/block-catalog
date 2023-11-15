=== Block Catalog ===
Contributors:      10up, dsawardekar, dkotter, jeffpaul
Tags:              gutenberg, developer, blocks, custom blocks
Requires at least: 5.7
Tested up to:      6.4
Requires PHP:      7.4
Stable tag:        1.5.2
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html

Easily keep track of which Gutenberg Blocks are used across your site.

== Description ==

* Find which blocks are used across your site.
* Fully Integrated with the WordPress Admin.
* Use filters to see Posts that use a specific block.
* Find Posts that use Reusable Blocks.
* Use the WP CLI to quickly find blocks from the command line.
* Use custom WordPress filters to extend the Block Catalog.

[Fork on GitHub](https://github.com/10up/block-catalog)

== Screenshots ==

1. The Block Catalog indexing page. You need to index your content first.
2. The Blocks found by the plugin on your site.
3. The Blocks for each post can be seen on the post listing page.
4. You can filter the post listing to a specific Block using this dropdown.

== Getting Started ==

1. On activation, the plugin will prompt you to index your content. You need to do this first before you will be able to see the various blocks used on your site. You can also go to *WP-Admin > Tools > Block Catalog* to do this yourself. Alternately, you can run the WP CLI command `wp block-catalog index` to index your content from the command line.

2. Once indexed, you will be able to see the different blocks used on your site in the Block Catalog Taxonomy.

3. Navigating to any Block Editor post type will also show you the list of blocks present in a post.

4. You can also filter the listing to only show Posts that have a specific block.

== Frequently Asked Questions ==

= 1) Why does the Plugin require indexing? =

Block Catalog uses a taxonomy to store the data about blocks used across a site. The plugin can build this index via the Tools > Block Catalog screen or via the WP CLI `wp block-catalog index`. After the initial index, the data is automatically kept in sync after any content updates.

= 2) Why does the name displayed in the plugin use the blockName attribute instead of the title? =

If your blocks are registered on the Backend with the old [register_block_type](https://developer.wordpress.org/reference/functions/register_block_type/) API, you may be missing the `title` attribute. The newer [register_block_type_from_metadata](https://developer.wordpress.org/reference/functions/register_block_type_from_metadata/) uses the same `block.json` on the FE and BE which includes the Block title.

When the plugin detects such a missing `title`, it uses the `blockName` suffix instead. eg:- xyz/custom-block will display as Custom Block.

To address this you need to update your custom block registration. If this is outside your control, you can also use the `block_catalog_block_title` filter hook to [override the title as seen here](https://gist.github.com/dsawardekar/676d0d4c5d7f688351e199fdc54484d6).

== Changelog ==

= 1.5.1 - 2023-10-24 =

**Note that this release changes the name of the base plugin file. As such, you'll probably need to reactivate the plugin after updating.**

* **Added:** Add our standard GitHub Action automations (props [@jeffpaul](https://github.com/jeffpaul), [@dsawardekar](https://github.com/dsawardekar), [@dkotter](https://github.com/dkotter) via [#10](https://github.com/10up/block-catalog/pull/10), [#20](https://github.com/10up/block-catalog/pull/20), [#22](https://github.com/10up/block-catalog/pull/22), [#23](https://github.com/10up/block-catalog/pull/23), [#24](https://github.com/10up/block-catalog/pull/24), [#25](https://github.com/10up/block-catalog/pull/25)).
* **Changed:** Update our plugin image assets (props [Brooke Campbell](https://www.linkedin.com/in/brookecampbelldesign/), [@jeffpaul](https://github.com/jeffpaul), [@dsawardekar](https://github.com/dsawardekar), [@faisal-alvi](https://github.com/faisal-alvi) via [#11](https://github.com/10up/block-catalog/pull/11), [#17](https://github.com/10up/block-catalog/pull/17)).
* **Changed:** Updated the main plugin file name (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc), [@dsawardekar](https://github.com/dsawardekar) via [#18](https://github.com/10up/block-catalog/pull/18)).
* **Security:** Bump `@babel/traverse` from 7.22.8 to 7.23.2 (props [@dependabot](https://github.com/apps/dependabot), [@dkotter](https://github.com/dkotter) via [#21](https://github.com/10up/block-catalog/pull/21)).

= 1.5.0 - 2023-08-11 =

* **Added:** `Beta` Support Level (props [@jeffpaul](https://github.com/jeffpaul), [@dsawardekar](https://github.com/dsawardekar) via [#3](https://github.com/10up/block-catalog/pull/3)).
* **Added:** Adds support for multisite via WP CLI (props [@dsawardekar](https://github.com/dsawardekar), [@Sidsector9](https://github.com/Sidsector9) via [#9](https://github.com/10up/block-catalog/pull/9)).
* **Fixed:** Missing name in the `block_catalog_taxonomy_options` hook (props [@dsawardekar](https://github.com/dsawardekar), [@fabiankaegy](https://github.com/fabiankaegy) via [#6](https://github.com/10up/block-catalog/pull/6)).

= 1.4.0 - 2022-12-03

- Improves Core Block Display Titles logic
- Fixes parent term for blocks registered without namespace
- Improve Reusable Block detection
- Add hooks to support nested variations
- Adds unit tests

= 1.3.2 - 2022-11-25 =

- Updates readme.txt

= 1.3.1 - 2022-11-25 =

- Minor docs updates

= 1.4.0 - 2022-12-03 =

- Improves Core Block Display Titles logic
- Fixes parent term for blocks registered without namespace
- Improve Reusable Block detection
- Add hooks to support nested variations
- Adds unit tests

= 1.3.0 - 2022-11-25 =

- Adds support for hierarchical classification
- Improves WP CLI find command
- Adds inline filter hook documentation
- Updates screenshots

= 1.2.2 - 2022-11-25 =

- Updates Documentation

= 1.2.1 - 2022-11-25 =

- Improves block title detection when default title is missing.
- Initial svn release

= 1.2.0 - 2022-11-24 =

- Improves filter output with wp_kses.

= 1.1.0 - 2022-11-23 =

- Improves batch indexing for larger sites.
- Refactor delete index to use batch mode.
- Improves error handling during indexing & deleting via WP-Admin.

= 1.0.1 - 2022-11-21 =

- Initial release

== Upgrade Notice ==

= 1.5.1 =

* Note that this release changes the name of the base plugin file. As such, you'll probably need to reactivate the plugin after updating

