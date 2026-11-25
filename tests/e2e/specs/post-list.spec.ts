/**
 * Posts list: the Block Catalog column and the "All Blocks" filter.
 */

/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import {
	HEADING_CONTENT,
	PARAGRAPH_CONTENT,
	deleteCatalog,
} from '../utils/helpers';

test.describe( 'Posts list', () => {
	test.afterEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
		await deleteCatalog( requestUtils );
	} );

	test( 'shows each post’s blocks and filters by block', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		const headingPost = await requestUtils.createPost( {
			title: 'Post with a heading',
			content: HEADING_CONTENT,
			status: 'publish',
		} );
		const paragraphPost = await requestUtils.createPost( {
			title: 'Post with a paragraph',
			content: PARAGRAPH_CONTENT,
			status: 'publish',
		} );

		await admin.visitAdminPage( 'edit.php' );

		const headingColumn = page.locator(
			`#post-${ headingPost.id } td.column-taxonomy-block-catalog`
		);
		await expect( headingColumn ).toContainText( 'Core' );
		await expect( headingColumn ).toContainText( 'Heading' );

		await page
			.locator( 'select[name="block-catalog"]' )
			.selectOption( 'core-heading' );
		await page.getByRole( 'button', { name: 'Filter' } ).click();

		await expect( page.locator( `#post-${ headingPost.id }` ) ).toBeVisible();
		await expect(
			page.locator( `#post-${ paragraphPost.id }` )
		).toHaveCount( 0 );
	} );
} );
