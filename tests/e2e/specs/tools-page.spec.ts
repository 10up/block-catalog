/**
 * Tools > Block Catalog: building and deleting the index.
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
	deleteCatalog,
	getCatalogTermNames,
} from '../utils/helpers';

test.describe( 'Block Catalog tools page', () => {
	test.beforeEach( async ( { requestUtils } ) => {
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

	test( 'is listed under Tools and indexes every post', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		// Saving the post catalogued it; start from an empty index.
		await deleteCatalog( requestUtils );
		expect( await getCatalogTermNames( requestUtils ) ).toEqual( [] );

		await admin.visitAdminPage( 'index.php' );
		await page.locator( '#menu-tools' ).hover();
		await page
			.locator( '#menu-tools' )
			.getByRole( 'link', { name: 'Block Catalog', exact: true } )
			.click();
		await expect( page ).toHaveURL( /tools\.php\?page=block-catalog-tools$/ );

		await page.getByRole( 'button', { name: 'Index Posts' } ).click();

		// Every post found was indexed, and there was at least one.
		await expect(
			page.getByText( /Indexed ([1-9]\d*) \/ \1 Posts Successfully\./ )
		).toBeVisible();
		expect( await getCatalogTermNames( requestUtils ) ).toEqual(
			expect.arrayContaining( [ 'Core', 'Heading' ] )
		);
	} );

	test( 'deletes the whole index', async ( { admin, page } ) => {
		await admin.visitAdminPage( 'tools.php', 'page=block-catalog-tools' );

		// The button asks for confirmation first.
		page.once( 'dialog', ( dialog ) => {
			expect( dialog.message() ).toContain(
				'This will delete all terms in the Block Catalog index.'
			);
			dialog.accept();
		} );
		await page.getByRole( 'button', { name: 'Delete Index' } ).click();

		await expect(
			page.getByText(
				/Deleted [1-9]\d* block catalog term\(s\) successfully\./
			)
		).toBeVisible();

		await admin.visitAdminPage( 'edit-tags.php', 'taxonomy=block-catalog' );
		await expect(
			page.locator( '.wp-list-table tbody tr.no-items' )
		).toHaveText( 'No block catalog found.' );
	} );
} );
