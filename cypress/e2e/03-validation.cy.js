/**
 * Tests CFDev validation errors (POST → redirect → GET cycle via ErrorBag transient).
 *
 * The demo meta box "cfdev_demo_flat" has Required() on many fields.
 * Leaving the text field empty triggers an error.
 *
 * On save with missing required fields:
 *   1. WordPress saves the post and redirects to the edit page.
 *   2. CFDev renders a .notice-error banner (admin_notices hook).
 *   3. The offending field row gets class .cfdev-has-error.
 *   4. A <p class="cfdev-field-error"> appears below the field.
 *
 * Note: there are many Required fields in the demo flat meta box.
 * Filling only ONE field will clear that field's error but others remain.
 * So we assert at the field level, not on the overall banner.
 */

describe('CFDev — Validation Errors', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/post-new.php')
    cy.setPostTitle('Cypress — Validation')
    cy.expandPostbox('cfdev_demo_flat')
    // Leave the required text field empty
    cy.get('input[name="cfdev[_text_demo_flat_text]"]').clear()
    cy.get('#publish').click()
    // After redirect, confirm we're on the edit page with errors
    cy.get('.notice-error').should('exist')
  })

  it('shows a notice-error banner when a Required field is empty', () => {
    cy.get('.notice-error').should('contain', 'field')
  })

  it('marks the field row with .cfdev-has-error', () => {
    cy.get('tr.cfdev-has-error').should('exist')
  })

  it('shows a .cfdev-field-error message under the field', () => {
    cy.get('p.cfdev-field-error').should('exist').and('not.be.empty')
  })

  it('each notice anchor points to an existing element in the DOM', () => {
    cy.get('.notice-error a[href^="#"]').each(($a) => {
      const targetId = $a.attr('href').slice(1)
      cy.get(`[id="${CSS.escape(targetId)}"]`).should('exist')
    })
  })

  it('clicking each notice anchor opens its container and makes the field visible', () => {
    // The existing JS handler already opens postbox + tab/accordion when an anchor is clicked.
    // This test verifies that full flow works for every anchor in the notice.
    cy.get('.notice-error a[href^="#"]').each(($a) => {
      const targetId = $a.attr('href').slice(1)
      cy.wrap($a).click()
      cy.get(`[id="${CSS.escape(targetId)}"]`).then(($el) => {
        // Flat fields: the input may be display:none (e.g. Image). Check the parent <tr>.
        // Bundle top-level: rendered outside a <table>, so no <tr> ancestor — check the div itself.
        const $row = $el.closest('tr')
        cy.wrap($row.length ? $row : $el).should('be.visible')
      })
    })
  })

  it('clears the error on a field once it is filled on next save', () => {
    // Value must satisfy all rules: Required, MinLength(3), MaxLength(50),
    // Contains('a'), StartsWith('A'), EndsWith('z')
    cy.get('input[name="cfdev[_text_demo_flat_text]"]').clear().type('Avalz')
    cy.get('#publish').click()

    // The text field row specifically should no longer have the error class
    cy.get('input[name="cfdev[_text_demo_flat_text]"]')
      .closest('tr')
      .should('not.have.class', 'cfdev-has-error')
  })
})
