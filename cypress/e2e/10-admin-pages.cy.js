/**
 * Tests CFDev Admin Pages: Registry, Inspector, Cache flush.
 *
 * Registry (/wp-admin/admin.php?page=cfdev):
 *   - Tab nav: post types (page/post), Terms, Users
 *   - Group cards: expand → fields table
 *   - Inspector modal: AJAX data load, cache badge, close
 *
 * Cache (/wp-admin/admin.php?page=cfdev-cache):
 *   - Toggle checkbox, file table with group tags
 *   - Flush all: success notice + empty state
 *
 * Tab activation: first tab is alphabetically first post type ('page' < 'post'),
 * so the post tab must be explicitly clicked before interacting with post groups.
 */

describe('CFDev — Admin Pages', () => {
  beforeEach(() => {
    cy.loginToWP()
  })

  // ── Registry: structure + group expand ──────────────────────────────────

  it('Registry — header count, tab nav, group expand and fields table', () => {
    cy.visit('/wp-admin/admin.php?page=cfdev')

    // Header: title + group count badge
    cy.get('.cfdev-header__title').should('contain', 'Field groups')
    cy.get('.cfdev-header__count').invoke('text').should('match', /\d+/)

    // Tab navigation has entries for post types + Terms + Users
    cy.get('.cfdev-tabs-nav a[href="#cfdev-tab-pt-post"]').should('exist')
    cy.get('.cfdev-tabs-nav a[href="#cfdev-tab-terms"]').should('exist')
    cy.get('.cfdev-tabs-nav a[href="#cfdev-tab-users"]').should('exist')

    // Click the Post tab (may not be active by default — 'page' comes first alphabetically)
    cy.get('a[href="#cfdev-tab-pt-post"]').click()
    cy.get('#cfdev-tab-pt-post').should('be.visible')
    cy.get('#cfdev-tab-pt-post .cfdev-group').should('have.length.gte', 2)

    // Expand the cfdev_demo_flat group
    cy.get('#cfdev-tab-pt-post')
      .contains('.cfdev-group-id', 'cfdev_demo_flat')
      .closest('.cfdev-group')
      .within(() => {
        cy.get('.cfdev-group-body').should('not.be.visible')
        cy.get('.cfdev-group-header').click()
        cy.get('.cfdev-group-body').should('be.visible')
        cy.get('.cfdev-fields-table').should('exist')
        cy.get('.cfdev-fields-table tbody tr').should('have.length.gte', 5)
        // Layout badge visible in header
        cy.get('.cfdev-badge--flat').should('exist')
        // Inspect button present
        cy.get('.cfdev-btn-inspect').should('exist')
      })

    // Terms tab: switch and verify term groups
    cy.get('a[href="#cfdev-tab-terms"]').click()
    cy.get('#cfdev-tab-terms').should('be.visible')
    cy.get('#cfdev-tab-terms .cfdev-group').should('have.length.gte', 1)

    // Users tab: switch and verify user groups
    cy.get('a[href="#cfdev-tab-users"]').click()
    cy.get('#cfdev-tab-users').should('be.visible')
    cy.get('#cfdev-tab-users .cfdev-group').should('have.length.gte', 1)
  })

  // ── Registry: Inspector modal ────────────────────────────────────────────

  it('Registry — Inspector modal loads field data via AJAX and closes', () => {
    cy.visit('/wp-admin/admin.php?page=cfdev')

    // Activate the post tab
    cy.get('a[href="#cfdev-tab-pt-post"]').click()

    // Click the Inspecter button on cfdev_demo_flat
    cy.get('#cfdev-tab-pt-post')
      .contains('.cfdev-group-id', 'cfdev_demo_flat')
      .closest('.cfdev-group')
      .find('.cfdev-btn-inspect')
      .click()

    // Modal is now visible
    cy.get('#cfdev-inspect-modal').should('be.visible')
    cy.get('.cfdev-modal-group-id').should('contain', 'cfdev_demo_flat')
    cy.get('.cfdev-modal-meta-label').should('contain', 'post')

    // AJAX resolves → tree renders (wait up to 10 s)
    cy.get('#cfdev-inspect-output .cfdev-tree', { timeout: 10000 }).should('exist')
    cy.get('#cfdev-inspect-output .cfdev-tree > li').should('have.length.gte', 1)

    // Cache badge is visible (cache is enabled)
    cy.get('#cfdev-inspect-cache-badge').should('be.visible')

    // Object select dropdown is shown (not a fixed-ID group)
    cy.get('#cfdev-inspect-toolbar').should('be.visible')
    cy.get('#cfdev-object-select option').should('have.length.gte', 1)

    // Close via × button
    cy.get('#cfdev-inspect-modal .cfdev-modal-close').click()
    cy.get('#cfdev-inspect-modal').should('not.be.visible')
  })

  // ── Cache page: shows files + flush all ─────────────────────────────────

  it('Cache — shows enabled toggle and file list, flush clears all files', () => {
    // Seed a cache entry via the cfdev REST endpoint.
    // The Inspector AJAX in test 2 may already have created one; this is a best-effort
    // top-up. Use a short timeout + failOnStatusCode:false so a slow/unavailable REST
    // response does not fail the test — the cache table assertion below will catch it.
    cy.request({
      url: '/wp-json/wp/v2/posts?per_page=1',
      failOnStatusCode: false,
      timeout: 8000,
    }).then(res => {
      if (res.status === 200) {
        const id = res.body[0]?.id
        if (id) {
          cy.request({ url: `/wp-json/cfdev/v1/post/${id}`, failOnStatusCode: false, timeout: 8000 })
        }
      }
    })

    cy.visit('/wp-admin/admin.php?page=cfdev-cache')

    // Header
    cy.get('.cfdev-header__title').should('contain', 'Cache')

    // Cache is enabled (cfdev_cache_enabled = 1 in DB)
    cy.get('#cfdev_cache_enabled').should('be.checked')

    // File table exists: seeded entry is listed
    cy.get('.cfdev-cache-table').should('exist')
    cy.get('.cfdev-cache-table tbody tr').should('have.length.gte', 1)
    cy.get('.cfdev-btn-flush').should('not.be.disabled')

    // Flush all
    cy.get('.cfdev-btn-flush').click()

    // Success notice
    cy.get('.notice-success').should('exist')

    // File table is gone; empty placeholder is shown
    cy.get('.cfdev-cache-table').should('not.exist')
    cy.get('.cfdev-placeholder').should('exist')
    cy.get('.cfdev-btn-flush').should('be.disabled')
  })
})
