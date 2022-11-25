=== Block Catalog ===
Contributors:      dsawardekar, 10up
Tags:              gutenberg, developer, blocks, custom blocks
Requires at least: 5.7
Tested up to:      6.1
Requires PHP:      7.4
Stable tag:        1.2.2
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

To address this you need to update your custom block registration. If this is outside your control, you can also use the `block_catalog_block_title` filter hook to override the title as seen below.

<pre><?php

add_filter( 'block_catalog_block_title', function( $title, $block_name, $block ) {
	$map = [
		"xyz/custom-block" => "My Custom Block",
	];

	if ( ! empty( $map[ $block_name ] ) ) {
		return $map[ $block_name ];
	}

	return $title;
}, 10, 3 );
</pre>

== Changelog ==

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


