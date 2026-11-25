/**
 * Shared E2E helpers.
 */

/**
 * WordPress dependencies
 */
import type { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

/** One heading block, as the block editor saves it. */
export const HEADING_CONTENT =
	'<!-- wp:heading --><h2 class="wp-block-heading">Catalog heading</h2><!-- /wp:heading -->';

/** One paragraph block, as the block editor saves it. */
export const PARAGRAPH_CONTENT =
	'<!-- wp:paragraph --><p>Catalog paragraph</p><!-- /wp:paragraph -->';

/**
 * Deletes every block catalog term through the plugin's own REST route.
 *
 * The taxonomy isn't in core REST (show_in_rest is false), and deleting
 * posts leaves their terms behind.
 *
 * @param requestUtils Logged-in request utils.
 */
export async function deleteCatalog( requestUtils: RequestUtils ) {
	await requestUtils.rest( {
		method: 'POST',
		path: '/block-catalog/v1/delete-index',
	} );
}

/**
 * Returns the names of every block catalog term.
 *
 * @param requestUtils Logged-in request utils.
 */
export async function getCatalogTermNames(
	requestUtils: RequestUtils
): Promise< string[] > {
	const { terms } = await requestUtils.rest< {
		terms: { name: string }[];
	} >( {
		method: 'POST',
		path: '/block-catalog/v1/terms',
	} );

	return terms.map( ( term ) => term.name );
}
