/**
 * Tests CFDev — WooCommerce compatibility.
 *
 * Requires WooCommerce + Classic Editor to be active.
 *
 * MetaBox registered: cfdev_wc_product on 'product'
 *   cfdev_wc_badge     → text
 *   cfdev_wc_note      → textarea
 *   cfdev_wc_highlight → toggle
 *   cfdev_wc_priority  → select (normal / high / urgent)
 *
 * TermMeta registered on 'product_cat'
 *   cfdev_wc_cat_banner → text
 *
 * Skipped: image, file, REST (WC uses its own /wc/v3 endpoints).
 */

const createAndEditProductCat = (name) => {
  cy.visit('/wp-admin/edit-tags.php?taxonomy=product_cat')
  cy.get('#tag-name').clear().type(name)
  cy.get('#submit').click()
  cy.contains('#the-list a.row-title', name, { timeout: 10000 })
    .invoke('attr', 'href')
    .then(href => cy.visit(href))
}

const saveTermAndReload = () => {
  cy.get('form#edittag [type="submit"]').first().click()
  cy.get('.notice-success, .updated, #message').should('exist')
}

describe('CFDev — WooCommerce compatibility', () => {
  beforeEach(() => {
    cy.loginToWP()
  })

  // ── Product MetaBox ─────────────────────────────────────────────────────────

  it('shows CFDev metabox on product edit screen', () => {
    cy.visit('/wp-admin/post-new.php?post_type=product')
    cy.expandPostbox('cfdev_wc_product')
    cy.get('#cfdev_wc_product').should('exist')
    cy.get('input[name="cfdev[cfdev_wc_badge]"]').should('exist')
    cy.get('textarea[name="cfdev[cfdev_wc_note]"]').should('exist')
    cy.get('input[name="cfdev[cfdev_wc_highlight]"]').should('exist')
    cy.get('select[name="cfdev[cfdev_wc_priority]"]').should('exist')
  })

  it('saves and restores all product CFDev fields', () => {
    cy.visit('/wp-admin/post-new.php?post_type=product')
    cy.setPostTitle('Cypress — WC Product')
    cy.expandPostbox('cfdev_wc_product')

    cy.get('input[name="cfdev[cfdev_wc_badge]"]').clear().type('Nouveauté')
    cy.get('textarea[name="cfdev[cfdev_wc_note]"]').clear().type('Offre limitée cette semaine')
    cy.get('input[name="cfdev[cfdev_wc_highlight]"]').check()
    cy.get('select[name="cfdev[cfdev_wc_priority]"]').select('high')

    cy.publishPost()
    cy.expandPostbox('cfdev_wc_product')

    cy.get('input[name="cfdev[cfdev_wc_badge]"]').should('have.value', 'Nouveauté')
    cy.get('textarea[name="cfdev[cfdev_wc_note]"]').should('have.value', 'Offre limitée cette semaine')
    cy.get('input[name="cfdev[cfdev_wc_highlight]"]').should('be.checked')
    cy.get('select[name="cfdev[cfdev_wc_priority]"]').should('have.value', 'high')
  })

  it('does not break WooCommerce product data metabox', () => {
    cy.visit('/wp-admin/post-new.php?post_type=product')
    cy.setPostTitle('Cypress — WC No Conflict')
    cy.expandPostbox('cfdev_wc_product')
    cy.get('input[name="cfdev[cfdev_wc_badge]"]').clear().type('Promo')

    cy.publishPost()

    // WC core metabox still present and functional
    cy.get('#woocommerce-product-data').should('exist')
    cy.expandPostbox('cfdev_wc_product')
    cy.get('input[name="cfdev[cfdev_wc_badge]"]').should('have.value', 'Promo')
  })

  // ── Product category TermMeta ────────────────────────────────────────────────

  it('shows CFDev fields on product_cat edit', () => {
    createAndEditProductCat(`Cypress — WC Cat ${Date.now()}`)
    cy.get('input[name="cfdev[cfdev_wc_cat_banner]"]').should('exist')
  })

  it('saves and restores product_cat CFDev field', () => {
    createAndEditProductCat(`Cypress — WC Cat Save ${Date.now()}`)

    cy.get('input[name="cfdev[cfdev_wc_cat_banner]"]').clear().type('Bannière catégorie test')
    saveTermAndReload()

    cy.get('input[name="cfdev[cfdev_wc_cat_banner]"]').should('have.value', 'Bannière catégorie test')
  })
})
