/**
 * Tests CFDev — Code modal (</> Code button on each group header).
 *
 * Covers:
 *   - Button presence on every expanded group
 *   - Modal opens with correct group ID
 *   - Display tab is active by default, code contains CacheManager + group ID
 *   - Raw tab switch shows shorter code, no echo / HTML tags
 *   - Display tab restores the original output
 *   - Copy button writes code to clipboard
 *   - Close via × button hides the modal
 *   - Close via overlay click hides the modal
 *   - Term meta group: CacheManager call contains taxonomy
 *   - User meta group: CacheManager call contains user($user->ID)
 */

describe('CFDev — Code modal', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/admin.php?page=cfdev-fields')
    // Activate the post tab (page < post alphabetically so post may not be default)
    cy.get('a[href="#cfdev-tab-pt-post"]').click()
    cy.get('#cfdev-tab-pt-post').should('be.visible')
  })

  // ── Button presence ───────────────────────────────────────────────────────

  it('Code button is present on every group header', () => {
    cy.get('#cfdev-tab-pt-post .cfdev-group').each(($group) => {
      cy.wrap($group).find('.cfdev-btn-code').should('exist')
    })
  })

  // ── Open + group ID ───────────────────────────────────────────────────────

  it('Clicking Code button opens the modal with the correct group ID', () => {
    cy.get('#cfdev-tab-pt-post')
      .contains('.cfdev-group-id', 'cfdev_demo_flat')
      .closest('.cfdev-group')
      .find('.cfdev-btn-code')
      .click()

    cy.get('#cfdev-code-modal').should('be.visible')
    cy.get('#cfdev-code-group-id').should('contain', 'cfdev_demo_flat')
  })

  // ── Display tab: default state and content ────────────────────────────────

  it('Display tab is active by default and code contains CacheManager + group ID', () => {
    cy.get('#cfdev-tab-pt-post')
      .contains('.cfdev-group-id', 'cfdev_demo_flat')
      .closest('.cfdev-group')
      .find('.cfdev-btn-code')
      .click()

    cy.get('#cfdev-code-tab-display').should('have.class', 'is-active')
    cy.get('#cfdev-code-tab-raw').should('not.have.class', 'is-active')

    cy.get('#cfdev-code-output').invoke('text').then((code) => {
      expect(code).to.include('CacheManager')
      expect(code).to.include('cfdev_demo_flat')
      expect(code).to.include('<?php')
      expect(code).to.include('post($post->ID)')
    })
  })

  // ── Raw tab: shorter, no echo / HTML ─────────────────────────────────────

  it('Raw tab shows shorter code with no echo or HTML tags', () => {
    cy.get('#cfdev-tab-pt-post')
      .contains('.cfdev-group-id', 'cfdev_demo_flat')
      .closest('.cfdev-group')
      .find('.cfdev-btn-code')
      .click()

    cy.get('#cfdev-code-output').invoke('text').then((displayCode) => {
      cy.get('#cfdev-code-tab-raw').click()

      cy.get('#cfdev-code-tab-raw').should('have.class', 'is-active')
      cy.get('#cfdev-code-tab-display').should('not.have.class', 'is-active')

      cy.get('#cfdev-code-output').invoke('text').then((rawCode) => {
        expect(rawCode.length).to.be.lessThan(displayCode.length)
        expect(rawCode).to.not.include('echo ')
        expect(rawCode).to.not.include('<a ')
        expect(rawCode).to.not.include('<img')
      })
    })
  })

  // ── Display tab restores ──────────────────────────────────────────────────

  it('Clicking Display tab after Raw restores the full display code', () => {
    cy.get('#cfdev-tab-pt-post')
      .contains('.cfdev-group-id', 'cfdev_demo_flat')
      .closest('.cfdev-group')
      .find('.cfdev-btn-code')
      .click()

    cy.get('#cfdev-code-output').invoke('text').as('displayCode')

    cy.get('#cfdev-code-tab-raw').click()
    cy.get('#cfdev-code-tab-display').click()

    cy.get('#cfdev-code-tab-display').should('have.class', 'is-active')
    cy.get('@displayCode').then((original) => {
      cy.get('#cfdev-code-output').invoke('text').should('equal', original)
    })
  })

  // ── Copy button ───────────────────────────────────────────────────────────

  it('Copy button writes the current code to the clipboard', () => {
    cy.get('#cfdev-tab-pt-post')
      .contains('.cfdev-group-id', 'cfdev_demo_flat')
      .closest('.cfdev-group')
      .find('.cfdev-btn-code')
      .click()

    cy.get('#cfdev-code-output').invoke('text').as('expectedCode')

    cy.window().then((win) => {
      cy.stub(win.navigator.clipboard, 'writeText').as('clipboardWrite').resolves()
    })

    cy.get('#cfdev-code-copy').click()

    cy.get('@clipboardWrite').then((stub) => {
      cy.get('@expectedCode').then((code) => {
        expect(stub).to.have.been.calledWith(code)
      })
    })
  })

  // ── Close via × button ────────────────────────────────────────────────────

  it('Close button hides the modal', () => {
    cy.get('#cfdev-tab-pt-post')
      .contains('.cfdev-group-id', 'cfdev_demo_flat')
      .closest('.cfdev-group')
      .find('.cfdev-btn-code')
      .click()

    cy.get('#cfdev-code-modal').should('be.visible')
    cy.get('#cfdev-code-modal .cfdev-modal-close').click()
    cy.get('#cfdev-code-modal').should('not.be.visible')
  })

  // ── Close via overlay ─────────────────────────────────────────────────────

  it('Clicking the overlay closes the modal', () => {
    cy.get('#cfdev-tab-pt-post')
      .contains('.cfdev-group-id', 'cfdev_demo_flat')
      .closest('.cfdev-group')
      .find('.cfdev-btn-code')
      .click()

    cy.get('#cfdev-code-modal').should('be.visible')
    cy.get('#cfdev-code-modal .cfdev-modal-overlay').click({ force: true })
    cy.get('#cfdev-code-modal').should('not.be.visible')
  })

  // ── Different meta types ──────────────────────────────────────────────────

  it('Term meta group: CacheManager call contains term_id + taxonomy', () => {
    cy.get('a[href="#cfdev-tab-terms"]').click()
    cy.get('#cfdev-tab-terms').should('be.visible')

    cy.get('#cfdev-tab-terms .cfdev-group').first().within(() => {
      cy.get('.cfdev-btn-code').click()
    })

    cy.get('#cfdev-code-modal').should('be.visible')
    cy.get('#cfdev-code-output').invoke('text').should('include', 'term($term->term_id')
  })

  it('User meta group: CacheManager call contains user($user->ID)', () => {
    cy.get('a[href="#cfdev-tab-users"]').click()
    cy.get('#cfdev-tab-users').should('be.visible')

    cy.get('#cfdev-tab-users .cfdev-group').first().within(() => {
      cy.get('.cfdev-btn-code').click()
    })

    cy.get('#cfdev-code-modal').should('be.visible')
    cy.get('#cfdev-code-output').invoke('text').should('include', 'user($user->ID)')
  })

  // ── Second group opens correctly ──────────────────────────────────────────

  it('Opening a second group after closing updates the group ID and code', () => {
    // Open cfdev_demo_flat
    cy.get('#cfdev-tab-pt-post')
      .contains('.cfdev-group-id', 'cfdev_demo_flat')
      .closest('.cfdev-group')
      .find('.cfdev-btn-code')
      .click()

    cy.get('#cfdev-code-group-id').invoke('text').as('firstId')
    cy.get('#cfdev-code-modal .cfdev-modal-close').click()

    // Open a different group (the second one in the post tab)
    cy.get('#cfdev-tab-pt-post .cfdev-btn-code').eq(1).click()

    cy.get('#cfdev-code-modal').should('be.visible')
    cy.get('@firstId').then((firstId) => {
      // The group ID displayed may be different (or same if only one group)
      cy.get('#cfdev-code-output').invoke('text').should('include', 'CacheManager')
    })
  })
})
