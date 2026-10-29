describe("Admin can login and open dashboard", () => {
	beforeEach(() => {
		cy.login();
	});

	it('Create post and insert heading', () => {
		cy.createPost({
			beforeSave: () => {
				cy.insertBlock('core/heading').then(id => {
					cy.getBlockEditor().find(`#${id}`).click().type('This is test heading');
				});
			},
		});
	});

	it("Ensure that the blocks catalog option is visible in 'Tools' and you can index post types.", () => {
		cy.visit("/wp-admin");
		cy.get("#menu-tools").scrollIntoView({ block: "center" }).trigger("mouseover").trigger("mouseenter");
		cy.get("#menu-tools .wp-submenu").scrollIntoView({ block: "center" }).should("exist");
		cy.get("#menu-tools .wp-submenu a").contains("Block Catalog").scrollIntoView({ block: "center" }).click({ force: true });
		cy.url().should("include", "tools.php?page=block-catalog");
		cy.get("#submit").click({ force: true });
		cy.contains(/Indexed (?!0 \/ 0)(\d+) \/ \1 Posts Successfully\./).should("exist");
	});

	it("Verify that you can see the block catalog in post type list view and filter it", () => {
		cy.visit("/wp-admin");
		cy.get("#menu-posts").contains('Posts').click();
		cy.get('.taxonomy-block-catalog.column-taxonomy-block-catalog').contains("Core").should("exist");
		cy.get('.taxonomy-block-catalog.column-taxonomy-block-catalog').contains("Heading").should("exist");
		cy.get('select[name="block-catalog"]').select('Heading');
		cy.get('#post-query-submit').click();
	});

	it("Ensure that block catalog list should be appeared when you index the posts.", () => {
		cy.visit("/wp-admin");
		cy.get("#menu-posts").contains('Posts').trigger("mouseover").trigger("mouseenter");
		cy.get("#menu-posts .wp-submenu").scrollIntoView({ block: "center" }).should("exist");
		cy.get("#menu-posts .wp-submenu a").contains("Block Catalog").scrollIntoView({ block: "center" }).click({ force: true });
		cy.get('#tag-search-input').type('Core');
		cy.get('#search-submit').click();
		cy.get('.name.column-name.has-row-actions.column-primary').contains("Core").should("exist");
		cy.get('.name.column-name.has-row-actions.column-primary').contains("Heading").should("exist");
	});

	it("Ensure that you can add new block catalog.", () => {
		const termName = Array.from({ length: 10 }, () => String.fromCharCode(97 + Math.floor(Math.random() * 26))).join('');

		cy.visit("/wp-admin");
		cy.get("#menu-posts").contains('Posts').trigger("mouseover").trigger("mouseenter");
		cy.get("#menu-posts .wp-submenu").scrollIntoView({ block: "center" }).should("exist");
		cy.get("#menu-posts .wp-submenu a").contains("Block Catalog").scrollIntoView({ block: "center" }).click({ force: true });
		cy.get('#tag-name').type(termName);
		cy.get('select[name="parent"]').select('Core');
		cy.get('#submit').click();
		cy.get('.notice-success').contains('Item added.').should('exist');
		cy.contains('.row-title', termName).should('exist');
	});

	it("Make sure indexed post type should be delete when you click on 'Delete Index' Button.", () => {
		cy.visit("/wp-admin");
		cy.get("#menu-tools").scrollIntoView({ block: "center" }).trigger("mouseover").trigger("mouseenter");
		cy.get("#menu-tools .wp-submenu").scrollIntoView({ block: "center" }).should("exist");
		cy.get("#menu-tools .wp-submenu a").contains("Block Catalog").scrollIntoView({ block: "center" }).click({ force: true });
		cy.url().should("include", "tools.php?page=block-catalog");
		cy.get("#delete-index").click({ force: true });
		cy.contains(/Deleted \d+ block catalog term\(s\) successfully\./).should("exist");
		cy.visit("/wp-admin/edit-tags.php?taxonomy=block-catalog");
		cy.get(".wp-list-table tbody tr").should("have.class", "no-items");
		cy.get(".wp-list-table tbody tr.no-items").contains("No block catalog found.").should("exist");
	});
});
