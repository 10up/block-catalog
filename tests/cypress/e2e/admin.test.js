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
    cy.visit("/wp-admin");
    cy.get("#menu-posts").contains('Posts').trigger("mouseover").trigger("mouseenter");
		cy.get("#menu-posts .wp-submenu").scrollIntoView({ block: "center" }).should("exist");
    cy.get("#menu-posts .wp-submenu a").contains("Block Catalog").scrollIntoView({ block: "center" }).click({ force: true });
		cy.get('#tag-name').type('Quote');
		cy.get('select[name="parent"]').select('Core');
		cy.get('#submit').click();
	});

	it("Make sure indexed post type should be delete when you click on 'Delete Index' Button.", () => {
    cy.visit("/wp-admin");
    cy.get("#menu-tools").scrollIntoView({ block: "center" }).trigger("mouseover").trigger("mouseenter");
    cy.get("#menu-tools .wp-submenu").scrollIntoView({ block: "center" }).should("exist");
    cy.get("#menu-tools .wp-submenu a").contains("Block Catalog").scrollIntoView({ block: "center" }).click({ force: true });
    cy.url().should("include", "tools.php?page=block-catalog");
		cy.get("#delete-index").click({ force: true });
  });

});


