/**
 * CFDev — REST Admin Page (/wp-admin/admin.php?page=cfdev-rest).
 *
 * Covers:
 *   - Page structure: header, dual toggle forms, tab nav, total count badge
 *   - REST toggle: disable / re-enable with label change
 *   - CFDev API toggle: same
 *   - Tab navigation + group expand → REST field table columns
 *   - Bundle modal: "View fields" button → modal content → close
 *
 * Demo data relevant to this spec:
 *   Post ('post'):  cfdev_demo_bundle — bundle _cfdev_demo_bundle with rest:true
 *   Page ('page'):  cfdev_demo_tabs  — _demo_tab_b_file with rest:true (tabs layout)
 *   Terms:          term accordion bundle with rest:true
 *   Options:        several pages with rest:true fields
 *
 * Tab order: 'page' < 'post' alphabetically → 'page' is the default active tab.
 */

describe('CFDev — REST Admin Page', () => {
  beforeEach(() => {
    cy.loginToWP()
  })

  // ── Page structure ─────────────────────────────────────────────────────────

  it('REST page — header, dual toggles, tab nav, total count badge', () => {
    cy.visit('/wp-admin/admin.php?page=cfdev-rest')

    // Header
    cy.get('.cfdev-header__title').should('contain', 'REST API')

    // Both toggle forms present
    cy.get('#cfdev_rest_enabled').should('exist')
    cy.get('#cfdev_api_enabled').should('exist')

    // Tab navigation exists with at least one tab
    cy.get('.cfdev-tabs-nav').should('exist')
    cy.get('.cfdev-tabs-nav a[data-cfdev-tab]').should('have.length.gte', 1)

    // Total count badge is present and shows a positive integer
    cy.get('.cfdev-rest-total')
      .invoke('text')
      .then((t) => parseInt(t.trim()))
      .should('be.gt', 0)
  })

  // ── REST toggle ───────────────────────────────────────────────────────────

  it('REST toggle — disabling shows "inactive" label, re-enabling restores "active"', () => {
    cy.visit('/wp-admin/admin.php?page=cfdev-rest')

    // Guarantee starting state: enabled
    cy.get('#cfdev_rest_enabled').check({ force: true })
    cy.get('#cfdev_rest_enabled').should('be.checked')

    // Disable
    cy.get('#cfdev_rest_enabled').uncheck({ force: true })
    cy.get('#cfdev_rest_enabled').should('not.be.checked')

    // Re-enable
    cy.get('#cfdev_rest_enabled').check({ force: true })
    cy.get('#cfdev_rest_enabled').should('be.checked')
  })

  // ── CFDev API toggle ──────────────────────────────────────────────────────

  it('CFDev API toggle — disabling shows "inactive" label, re-enabling restores "active"', () => {
    cy.visit('/wp-admin/admin.php?page=cfdev-rest')

    // Guarantee starting state: enabled
    cy.get('#cfdev_api_enabled').check({ force: true })
    cy.get('#cfdev_api_enabled').should('be.checked')

    // Disable
    cy.get('#cfdev_api_enabled').uncheck({ force: true })
    cy.get('#cfdev_api_enabled').should('not.be.checked')

    // Re-enable
    cy.get('#cfdev_api_enabled').check({ force: true })
    cy.get('#cfdev_api_enabled').should('be.checked')
  })

  // ── Tab navigation + group expand ─────────────────────────────────────────

  it('REST tab nav — switching to post tab shows groups, expand reveals REST table', () => {
    cy.visit('/wp-admin/admin.php?page=cfdev-rest')

    // Switch to the 'post' tab (not active by default — 'page' comes first)
    cy.get('a[href="#cfdev-rest-tab-pt-post"]').click()
    cy.get('#cfdev-rest-tab-pt-post').should('be.visible')

    // At least one group is listed
    cy.get('#cfdev-rest-tab-pt-post .cfdev-group').should('have.length.gte', 1)

    // Expand the cfdev_demo_bundle group
    cy.get('#cfdev-rest-tab-pt-post')
      .contains('.cfdev-group-id', 'cfdev_demo_bundle')
      .closest('.cfdev-group')
      .within(() => {
        cy.get('.cfdev-group-body').should('have.attr', 'hidden')
        cy.get('.cfdev-group-header').click()
        cy.get('.cfdev-group-body').should('not.have.attr', 'hidden')

        // REST table is present with all five columns
        cy.get('.cfdev-rest-table').should('exist')
        cy.get('.cfdev-rest-table thead th').should('have.length', 5)
      })
  })

  // ── Bundle modal ──────────────────────────────────────────────────────────

  it('REST bundle modal — "View fields" opens modal with field list, close works', () => {
    cy.visit('/wp-admin/admin.php?page=cfdev-rest')

    // Modal is hidden initially
    cy.get('#cfdev-rest-bundle-modal').should('have.attr', 'hidden')

    // Switch to post tab and expand cfdev_demo_bundle
    cy.get('a[href="#cfdev-rest-tab-pt-post"]').click()
    cy.get('#cfdev-rest-tab-pt-post')
      .contains('.cfdev-group-id', 'cfdev_demo_bundle')
      .closest('.cfdev-group')
      .within(() => {
        cy.get('.cfdev-group-header').click()
        // Click "View fields" on the bundle row
        cy.get('.cfdev-bundle-fields-btn').first().click()
      })

    // Modal is now visible
    cy.get('#cfdev-rest-bundle-modal').should('not.have.attr', 'hidden')

    // Modal header shows bundle key
    cy.get('#cfdev-rest-bundle-key').should('not.be.empty')

    // Bundle body lists at least one field row
    cy.get('#cfdev-rest-bundle-body table tbody tr').should('have.length.gte', 1)

    // Close via × button
    cy.get('#cfdev-rest-bundle-modal .cfdev-modal-close').click()
    cy.get('#cfdev-rest-bundle-modal').should('have.attr', 'hidden')
  })

  // ── Terms and Options tabs ────────────────────────────────────────────────

  it('REST terms tab — exists and lists at least one group', () => {
    cy.visit('/wp-admin/admin.php?page=cfdev-rest')

    cy.get('a[href="#cfdev-rest-tab-terms"]').click()
    cy.get('#cfdev-rest-tab-terms').should('be.visible')
    cy.get('#cfdev-rest-tab-terms .cfdev-group').should('have.length.gte', 1)
  })

  it('REST options tab — exists and lists at least one group', () => {
    cy.visit('/wp-admin/admin.php?page=cfdev-rest')

    cy.get('a[href="#cfdev-rest-tab-options"]').click()
    cy.get('#cfdev-rest-tab-options').should('be.visible')
    cy.get('#cfdev-rest-tab-options .cfdev-group').should('have.length.gte', 1)
  })
})