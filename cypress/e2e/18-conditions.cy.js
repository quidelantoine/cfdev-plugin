/**
 * CFDev — onlyWhen() location conditions on MetaBox.
 *
 * Demo fixtures (demo-cypress.php):
 *   cfdev_cypress_cond_admin   — onlyWhen(current_user_can('administrator'))
 *                                Condition passes: Cypress logs in as admin.
 *   cfdev_cypress_cond_pending — onlyWhen(post_status === 'pending')
 *                                Condition fails: posts are never 'pending' in this suite.
 *
 * Covers:
 *   - Metabox present in DOM when condition passes
 *   - Metabox absent from DOM when condition fails
 *   - Field in passing metabox saves correctly
 *   - Dashboard shows the callable_conditions badge
 */

describe('CFDev — onlyWhen conditions', () => {
  beforeEach(() => {
    cy.loginToWP()
  })

  // ── Display ────────────────────────────────────────────────────────────────

  it('metabox with passing onlyWhen is present in the DOM', () => {
    cy.visit('/wp-admin/post-new.php')
    cy.get('#cfdev_cypress_cond_admin').should('exist')
  })

  it('metabox with failing onlyWhen is absent from the DOM', () => {
    cy.visit('/wp-admin/post-new.php')
    cy.get('#cfdev_cypress_cond_pending').should('not.exist')
  })

  // ── Save ───────────────────────────────────────────────────────────────────

  it('field inside a passing onlyWhen metabox saves and restores correctly', () => {
    cy.visit('/wp-admin/post-new.php')
    cy.setPostTitle('Cypress — onlyWhen save')
    cy.expandPostbox('cfdev_cypress_cond_admin')

    cy.get('input[name="cfdev[_cond_admin_text]"]').clear().type('condition-passed')
    cy.publishPost()

    // Page reloads after publish — field value must be restored
    cy.get('input[name="cfdev[_cond_admin_text]"]').should('have.value', 'condition-passed')
  })

  // ── Dashboard badge ────────────────────────────────────────────────────────

  it('Dashboard shows the callable_conditions badge for the group', () => {
    cy.visit('/wp-admin/admin.php?page=cfdev')
    cy.get('a[href="#cfdev-tab-pt-post"]').click()

    cy.get('#cfdev-tab-pt-post')
      .contains('.cfdev-group-id', 'cfdev_cypress_cond_admin')
      .closest('.cfdev-group')
      .within(() => {
        // Badge is in the group header — no need to expand
        cy.get('.cfdev-condition-badge').should('exist')
      })
  })
})