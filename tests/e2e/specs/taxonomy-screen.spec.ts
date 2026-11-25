/**
 * Posts > Block Catalog: the taxonomy screen.
 */

/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import { HEADING_CONTENT, deleteCatalog } from '../utils/helpers';

test.describe( 'Block Catalog taxonomy screen', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		// Saving the post catalogues it under Core > Heading.
		await requestUtils.createPost( {
			title: 'Post with a heading',
			content: HEADING_CONTENT,
			status: 'publish',
		} );
	} );

	test.afterEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
		await deleteCatalog( requestUtils );
	} );

	test( 'is listed under Posts and finds indexed blocks', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage( 'index.php' );
		await page.locator( '#menu-posts' ).hover();
		await page
			.locator( '#menu-posts' )
			.getByRole( 'link', { name: 'Block Catalog', exact: true } )
			.click();
		await expect( page ).toHaveURL(
			/edit-tags\.php\?taxonomy=block-catalog/
		);

		await page.locator( '#tag-search-input' ).fill( 'Core' );
		await page.locator( '#search-submit' ).click();

		const names = page.locator( '#the-list .column-name' );
		await expect( names.filter( { hasText: 'Core' } ) ).not.toHaveCount( 0 );
		await expect( names.filter( { hasText: 'Heading' } ) ).toHaveCount( 1 );
	} );

	test( 'adds a block catalog term under a parent', async ( {
		admin,
		page,
	} ) => {
		const termName = `Custom term ${ Date.now() }`;

		await admin.visitAdminPage( 'edit-tags.php', 'taxonomy=block-catalog' );

		await page.locator( '#tag-name' ).fill( termName );
		await page.locator( 'select[name="parent"]' ).selectOption( {
			label: 'Core',
		} );
		await page.locator( '#submit' ).click();

		await expect(
			page.locator( '#ajax-response .notice-success' )
		).toContainText( 'Item added.' );
		await expect(
			page.locator( '#the-list .row-title', { hasText: termName } )
		).toBeVisible();
	} );
} );
