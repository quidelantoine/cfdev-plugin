/**
 * Tests CFDev front-end rendering via the "CFDev Test" page template.
 *
 * Template: themes/webvite/template-cfdev-test.php
 * URL:      /?pagename=cfdev-test&post_id={id}  (plain query var — bypasse la résolution de slug prettifié)
 *
 * The template accepts ?post_id= to load data for any post. The before() hook
 * creates a published post with known values via the WP admin, then each it()
 * visits the front-end URL with that post ID and asserts the rendered HTML.
 *
 * Selectors: [data-cfdev="field_id"] for flat fields,
 *            [data-cfdev-bundle-row="n"] [data-cfdev="field_id"] for bundle.
 *
 * Fields seeded:
 *   Flat: text, select, toggle(on), yesno(yes), radios(v3),
 *         checkboxes(v1+v2), color, date
 *   Bundle: row 0 (text='FrontBundle1', select='v1')
 *           row 1 (text='FrontBundle2', select='v3')
 */

// ── Flat field form names ─────────────────────────────────────────────────────
const F = {
  text:       'cfdev[_text_demo_flat_text]',
  select:     'cfdev[_text_demo_flat_select]',
  toggle:     'cfdev[_text_demo_flat_toggle]',
  yesno:      'cfdev[_text_demo_flat_yesno]',
  radios:     'cfdev[_text_demo_flat_radios][]',
  checkboxes: 'cfdev[_text_demo_flat_checkboxes][]',
  color:      'cfdev[_text_demo_flat_color]',
  date:       'cfdev[_date_demo_flat_date]',
}

// ── Bundle helpers ────────────────────────────────────────────────────────────
const BUNDLE_ID = '_cfdev_demo_bundle'
const BF = {
  text:   '_text_demo_bundle_text',
  select: '_text_demo_bundle_select',
}
const nb = (row, fieldId) => `cfdev[${BUNDLE_ID}][${row}][${fieldId}]`

// ── Seeded values ─────────────────────────────────────────────────────────────
const FLAT_TEXT   = 'FrontText'
const FLAT_SELECT = 'v2'
const FLAT_COLOR  = '#cc0066'
const FLAT_DATE   = '06/15/2026'
const B0_TEXT     = 'FrontBundle1'
const B0_SELECT   = 'v1'
const B1_TEXT     = 'FrontBundle2'
const B1_SELECT   = 'v3'

let postId

describe('CFDev — Front-end Rendering', () => {
  // The webvite theme raises "Transition was skipped" from its JS router during
  // navigation — this is unrelated to CFDev. Suppress it so the assertions run.
  beforeEach(() => {
    cy.loginToWP()
    cy.on('uncaught:exception', err => {
      if (err.message && err.message.includes('Transition was skipped')) {
        return false
      }
    })
  })

  before(() => {
    cy.loginToWP()

    // Create a new post and seed known field values
    cy.visit('/wp-admin/post-new.php')
    cy.get('#title').clear().type('CFDev Front-end Test Post').blur()
    cy.expandPostbox('cfdev_demo_flat')

    // Flat fields
    cy.get(`input[name="${F.text}"]`).clear().type(FLAT_TEXT)
    cy.get(`select[name="${F.select}"]`).select(FLAT_SELECT)
    cy.get(`input[name="${F.toggle}"]`).check()
    cy.get(`input[name="${F.yesno}"][value="yes"]`).check()
    cy.get(`input[name="${F.radios}"][value="v3"]`).check()
    cy.get(`input[name="${F.checkboxes}"][value="v1"]`).check()
    cy.get(`input[name="${F.checkboxes}"][value="v2"]`).check()
    cy.get(`input[name="${F.checkboxes}"][value="v3"]`).uncheck()
    cy.get(`input[name="${F.color}"]`).invoke('val', FLAT_COLOR).trigger('change', { force: true })
    cy.get(`input[name="${F.date}"]`).invoke('val', FLAT_DATE).trigger('change', { force: true })

    // Bundle: ensure exactly 1 row, fill it, then add row 1
    cy.expandPostbox('cfdev_demo_bundle')
    cy.get(`#${BUNDLE_ID} .js-cfdev-sortable-item`).then($rows => {
      const extra = $rows.length - 1
      for (let i = 0; i < extra; i++) {
        cy.get(`#${BUNDLE_ID} .js-cfdev-remove-sortable`).last().click()
      }
    })
    cy.get(`input[name="${nb(0, BF.text)}"]`).invoke('val', B0_TEXT).trigger('input')
    cy.get(`select[name="${nb(0, BF.select)}"]`).select(B0_SELECT)
    cy.get(`#${BUNDLE_ID} .js-cfdev-add-bundle`).click()
    cy.get(`input[name="${nb(1, BF.text)}"]`).invoke('val', B1_TEXT).trigger('input')
    cy.get(`select[name="${nb(1, BF.select)}"]`).select(B1_SELECT)

    cy.publishPost()

    // Capture the post ID from the URL after publish
    cy.url().then(url => {
      const m = url.match(/[?&]post=(\d+)/)
      postId = m ? parseInt(m[1]) : null
      expect(postId).to.be.greaterThan(0)
    })
  })

  // ── Flat fields ─────────────────────────────────────────────────────────────

  it('renders flat field values in [data-cfdev] elements', () => {
    cy.then(() => cy.visit(`/?pagename=cfdev-test&post_id=${postId}`))

    cy.get('[data-cfdev="_text_demo_flat_text"]').should('have.text', FLAT_TEXT)
    cy.get('[data-cfdev="_text_demo_flat_select"]').should('have.text', FLAT_SELECT)
    cy.get('[data-cfdev="_text_demo_flat_toggle"]').should('have.text', 'on')
    cy.get('[data-cfdev="_text_demo_flat_yesno"]').should('have.text', 'yes')
    cy.get('[data-cfdev="_text_demo_flat_radios"]').should('have.text', 'v3')
    cy.get('[data-cfdev="_text_demo_flat_checkboxes"]')
      .should('have.attr', 'data-values', 'v1,v2')
      .and('have.text', 'v1,v2')
    cy.get('[data-cfdev="_text_demo_flat_color"]').should('have.text', FLAT_COLOR)
    cy.get('[data-cfdev="_date_demo_flat_date"]').should('have.text', FLAT_DATE)
  })

  // ── Bundle rows ──────────────────────────────────────────────────────────────

  it('renders bundle rows with correct values per row', () => {
    cy.then(() => cy.visit(`/?pagename=cfdev-test&post_id=${postId}`))

    cy.get('#cfdev-bundle [data-cfdev-bundle-row]').should('have.length', 2)

    cy.get('[data-cfdev-bundle-row="0"] [data-cfdev="_text_demo_bundle_text"]').should('have.text', B0_TEXT)
    cy.get('[data-cfdev-bundle-row="0"] [data-cfdev="_text_demo_bundle_select"]').should('have.text', B0_SELECT)

    cy.get('[data-cfdev-bundle-row="1"] [data-cfdev="_text_demo_bundle_text"]').should('have.text', B1_TEXT)
    cy.get('[data-cfdev-bundle-row="1"] [data-cfdev="_text_demo_bundle_select"]').should('have.text', B1_SELECT)
  })

  // ── REST ↔ front-end parity ──────────────────────────────────────────────────

  it('REST endpoint returns the same flat and bundle data as the front-end', () => {
    cy.then(() => {
      cy.request(`/wp-json/cfdev/v1/post/${postId}`).then(res => {
        expect(res.status).to.eq(200)

        const flat = res.body.groups?.cfdev_demo_flat
        expect(flat).to.have.property('_text_demo_flat_text', FLAT_TEXT)
        expect(flat).to.have.property('_text_demo_flat_select', FLAT_SELECT)

        const bundle = res.body.groups?.cfdev_demo_bundle?.[BUNDLE_ID]
        expect(bundle).to.be.an('array').with.length(2)
        expect(bundle[0]).to.have.property(BF.text, B0_TEXT)
        expect(bundle[0]).to.have.property(BF.select, B0_SELECT)
        expect(bundle[1]).to.have.property(BF.text, B1_TEXT)
        expect(bundle[1]).to.have.property(BF.select, B1_SELECT)
      })
    })
  })
})
