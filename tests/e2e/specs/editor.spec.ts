/**
 * Block editor: saving a post catalogues its blocks.
 */

/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import { deleteCatalog } from '../utils/helpers';

test.describe( 'Block editor', () => {
	test.afterEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
		await deleteCatalog( requestUtils );
	} );

	test( 'catalogues the blocks of a post published in the editor', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( { title: 'Post from the editor' } );
		await editor.insertBlock( {
			name: 'core/heading',
			attributes: { content: 'This is test heading' },
		} );
		const postId = await editor.publishPost();

		await admin.visitAdminPage( 'edit.php' );

		const column = page.locator(
			`#post-${ postId } td.column-taxonomy-block-catalog`
		);
		await expect( column ).toContainText( 'Core' );
		await expect( column ).toContainText( 'Heading' );
	} );
} );
