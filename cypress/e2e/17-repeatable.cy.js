/**
 * Tests repeatable fields on Post edit screen.
 * MetaBox: cfdev_cypress_repeatable ("[CYPRESS] Repeatable")
 * Fields:  _rep_text (text), _rep_number (number), _rep_email (email), _rep_select (select)
 *
 * POST names: cfdev[_rep_text][], cfdev[_rep_number][], cfdev[_rep_email][], cfdev[_rep_select][]
 *
 * Drag-reorder: handles and jQuery UI sortable init are verified, but pixel-drag
 * is not tested — add @4tw/cypress-drag-drop if that coverage is needed.
 */

const BOX = 'cfdev_cypress_repeatable'

// Scopes to the <td> containing inputs/selects for a given field name fragment.
// e.g. tdFor('_rep_text') targets the <td> with name="cfdev[_rep_text][]"
function tdFor (nameFragment) {
  return cy.get(`[name*="${nameFragment}"]`).first().closest('td.cfdev-td')
}

// ─────────────────────────────────────────────────────────────────────────────

describe('CFDev — Repeatable Fields (Post)', () => {
  beforeEach(() => {
    cy.loginToWP()
  })

  // ---------------------------------------------------------------------------
  // Render initial state
  // ---------------------------------------------------------------------------

  it('shows the repeatable metabox with one row per field', () => {
    cy.visit('/wp-admin/post-new.php')
    cy.expandPostbox(BOX)

    // One sortable item per field on initial render
    tdFor('_rep_text').find('.js-cfdev-sortable-item').should('have.length', 1)
    tdFor('_rep_number').find('.js-cfdev-sortable-item').should('have.length', 1)
    tdFor('_rep_email').find('.js-cfdev-sortable-item').should('have.length', 1)
    tdFor('_rep_select').find('.js-cfdev-sortable-item').should('have.length', 1)

    // "+ Add" button present for each field
    tdFor('_rep_text').find('.js-cfdev-add-sortable').should('exist')

    // No remove button when only one row
    tdFor('_rep_text').find('.js-cfdev-remove-sortable').should('not.exist')
  })

  // ---------------------------------------------------------------------------
  // Add row
  // ---------------------------------------------------------------------------

  it('adds a row on "+ Add" click and shows remove buttons on all rows', () => {
    cy.visit('/wp-admin/post-new.php')
    cy.expandPostbox(BOX)

    tdFor('_rep_text').within(() => {
      cy.get('.js-cfdev-add-sortable').click()
      cy.get('.js-cfdev-sortable-item').should('have.length', 2)
      // Both rows now have a remove button
      cy.get('.js-cfdev-remove-sortable').should('have.length', 2)
    })
  })

  it('adds multiple rows independently per field', () => {
    cy.visit('/wp-admin/post-new.php')
    cy.expandPostbox(BOX)

    tdFor('_rep_text').find('.js-cfdev-add-sortable').click()
    tdFor('_rep_text').find('.js-cfdev-add-sortable').click()
    tdFor('_rep_text').find('.js-cfdev-sortable-item').should('have.length', 3)

    // Other fields are not affected
    tdFor('_rep_number').find('.js-cfdev-sortable-item').should('have.length', 1)
  })

  // ---------------------------------------------------------------------------
  // Remove row
  // ---------------------------------------------------------------------------

  it('removes a row and hides remove buttons when only one row remains', () => {
    cy.visit('/wp-admin/post-new.php')
    cy.expandPostbox(BOX)

    tdFor('_rep_text').within(() => {
      cy.get('.js-cfdev-add-sortable').click()
      cy.get('.js-cfdev-sortable-item').should('have.length', 2)

      // Remove the last row
      cy.get('.js-cfdev-remove-sortable').last().click()
      cy.get('.js-cfdev-sortable-item').should('have.length', 1)

      // Remove button is gone when only one row remains
      cy.get('.js-cfdev-remove-sortable').should('not.exist')
    })
  })

  // ---------------------------------------------------------------------------
  // Save + reload — persistence
  // ---------------------------------------------------------------------------

  it('saves multiple values and restores them on reload', () => {
    cy.visit('/wp-admin/post-new.php')
    cy.setPostTitle('Cypress — Repeatable persistence')
    cy.expandPostbox(BOX)

    // text: 2 rows
    tdFor('_rep_text').within(() => {
      cy.get('[name*="_rep_text"]').clear().type('alpha')
      cy.get('.js-cfdev-add-sortable').click()
      cy.get('[name*="_rep_text"]').last().type('beta')
    })

    // number: 2 rows
    tdFor('_rep_number').within(() => {
      cy.get('[name*="_rep_number"]').clear().type('10')
      cy.get('.js-cfdev-add-sortable').click()
      cy.get('[name*="_rep_number"]').last().type('20')
    })

    // email: 2 rows
    tdFor('_rep_email').within(() => {
      cy.get('[name*="_rep_email"]').clear().type('a@example.com')
      cy.get('.js-cfdev-add-sortable').click()
      cy.get('[name*="_rep_email"]').last().type('b@example.com')
    })

    // select: 2 rows
    tdFor('_rep_select').within(() => {
      cy.get('[name*="_rep_select"]').first().select('review')
      cy.get('.js-cfdev-add-sortable').click()
      cy.get('[name*="_rep_select"]').last().select('done')
    })

    cy.publishPost()

    // Verify after reload
    cy.expandPostbox(BOX)

    tdFor('_rep_text').find('[name*="_rep_text"]').should('have.length', 2)
    tdFor('_rep_text').find('[name*="_rep_text"]').eq(0).should('have.value', 'alpha')
    tdFor('_rep_text').find('[name*="_rep_text"]').eq(1).should('have.value', 'beta')

    tdFor('_rep_number').find('[name*="_rep_number"]').eq(0).should('have.value', '10')
    tdFor('_rep_number').find('[name*="_rep_number"]').eq(1).should('have.value', '20')

    tdFor('_rep_email').find('[name*="_rep_email"]').eq(0).should('have.value', 'a@example.com')
    tdFor('_rep_email').find('[name*="_rep_email"]').eq(1).should('have.value', 'b@example.com')

    tdFor('_rep_select').find('[name*="_rep_select"]').eq(0).should('have.value', 'review')
    tdFor('_rep_select').find('[name*="_rep_select"]').eq(1).should('have.value', 'done')
  })

  // ---------------------------------------------------------------------------
  // Input order → save order preserved
  // ---------------------------------------------------------------------------

  it('preserves the order of rows after save', () => {
    cy.visit('/wp-admin/post-new.php')
    cy.setPostTitle('Cypress — Repeatable order')
    cy.expandPostbox(BOX)

    tdFor('_rep_text').within(() => {
      cy.get('[name*="_rep_text"]').clear().type('first')
      cy.get('.js-cfdev-add-sortable').click()
      cy.get('[name*="_rep_text"]').last().type('second')
      cy.get('.js-cfdev-add-sortable').click()
      cy.get('[name*="_rep_text"]').last().type('third')
    })

    cy.publishPost()
    cy.expandPostbox(BOX)

    tdFor('_rep_text').find('[name*="_rep_text"]').then($inputs => {
      expect($inputs).to.have.length(3)
      expect($inputs.eq(0).val()).to.equal('first')
      expect($inputs.eq(1).val()).to.equal('second')
      expect($inputs.eq(2).val()).to.equal('third')
    })
  })

  // ---------------------------------------------------------------------------
  // Remove one value, save, verify remaining values
  // ---------------------------------------------------------------------------

  it('saves correctly after removing a row', () => {
    cy.visit('/wp-admin/post-new.php')
    cy.setPostTitle('Cypress — Repeatable remove-save')
    cy.expandPostbox(BOX)

    tdFor('_rep_text').within(() => {
      cy.get('[name*="_rep_text"]').clear().type('keep-a')
      cy.get('.js-cfdev-add-sortable').click()
      cy.get('[name*="_rep_text"]').last().type('remove-me')
      cy.get('.js-cfdev-add-sortable').click()
      cy.get('[name*="_rep_text"]').last().type('keep-b')
      // Remove the middle row (index 1)
      cy.get('.js-cfdev-remove-sortable').eq(1).click()
    })

    cy.publishPost()
    cy.expandPostbox(BOX)

    tdFor('_rep_text').find('[name*="_rep_text"]').then($inputs => {
      expect($inputs).to.have.length(2)
      expect($inputs.eq(0).val()).to.equal('keep-a')
      expect($inputs.eq(1).val()).to.equal('keep-b')
    })
  })

  // ---------------------------------------------------------------------------
  // Drag handle — present and sortable initialized (no pixel-drag)
  // ---------------------------------------------------------------------------

  it('shows drag handles and initializes jQuery UI sortable after adding rows', () => {
    cy.visit('/wp-admin/post-new.php')
    cy.expandPostbox(BOX)

    tdFor('_rep_text').within(() => {
      cy.get('.js-cfdev-add-sortable').click()
      cy.get('.js-cfdev-handle-sortable').should('have.length', 2)
    })

    // jQuery UI sortable is initialized on the list
    cy.window().then(win => {
      tdFor('_rep_text').find('.js-cfdev-sortable').then($list => {
        const isInit = win.jQuery($list[0]).data('ui-sortable') !== undefined
        expect(isInit).to.be.true
      })
    })
  })
})