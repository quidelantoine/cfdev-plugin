/**
 * Tests the CFDev Bundle field on the Post edit screen.
 *
 * Meta box:  cfdev_demo_bundle  ("[DEMO] Bundle")
 * Bundle ID: _cfdev_demo_bundle  ← Bundle::buildId() prepends underscore
 * Input name pattern: cfdev[_cfdev_demo_bundle][ROW][field_id]
 *
 * Skipped: image, image_alt, gallery, file, link, wysiwyg, post_select,
 *          term_select, user_select, post_checkboxes, term_checkboxes, user_checkboxes
 */

const BUNDLE = '_cfdev_demo_bundle'
const BOX    = '#cfdev_demo_bundle'
const ADD    = `${BOX} .js-cfdev-add-bundle`
const ITEMS  = `${BOX} .js-cfdev-sortable-item`
const REMOVE = `${BOX} .js-cfdev-remove-sortable`

const BF = {
  text:       '_text_demo_bundle_text',
  textarea:   '_text_demo_bundle_textarea',
  qty:        '_text_demo_bundle_qty',
  range:      '_text_demo_bundle_range',
  email:      '_text_demo_bundle_email',
  url:        '_text_demo_bundle_website',
  tel:        '_text_demo_bundle_phone',
  toggle:     '_text_demo_bundle_toggle',
  checkbox:   '_text_demo_bundle_checkbox',
  checkboxes: '_text_demo_bundle_checkboxes',
  radios:     '_text_demo_bundle_radios',
  yesno:      '_text_demo_bundle_yesno',
  select:     '_text_demo_bundle_select',
  color:      '_text_demo_bundle_color',
  date:       '_date_demo_bundle_date',
}

const n = (row, fieldId) => `cfdev[${BUNDLE}][${row}][${fieldId}]`

describe('CFDev — Bundle Fields (Post)', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/post-new.php')
    cy.expandPostbox('cfdev_demo_bundle')
  })

  it('shows bundle structure: add row, verify count, remove row', () => {
    cy.get(BOX).should('exist')
    cy.get(ADD).should('be.visible')
    cy.get(ITEMS).should('have.length', 1)
    cy.get(REMOVE).should('not.exist')
    cy.get(ADD).click()
    cy.get(ITEMS).should('have.length', 2)
    cy.get(REMOVE).should('have.length', 2)
    cy.get(REMOVE).last().click()
    cy.get(ITEMS).should('have.length', 1)
    cy.get(REMOVE).should('not.exist')
  })

  it('saves and restores all fields in bundle row 0', () => {
    cy.setPostTitle('Cypress — Bundle All Fields')

    cy.get(`input[name="${n(0, BF.text)}"]`).clear().type('BundleText')
    cy.get(`textarea[name="${n(0, BF.textarea)}"]`).clear().type('Bundle textarea')
    cy.get(`input[name="${n(0, BF.qty)}"]`).clear().type('7')
    cy.get(`input[name="${n(0, BF.range)}"]`).invoke('val', '80').trigger('change')
    cy.get(`input[name="${n(0, BF.email)}"]`).clear().type('bundle@cypress.io')
    cy.get(`input[name="${n(0, BF.url)}"]`).clear().type('https://bundle.cypress.io')
    cy.get(`input[name="${n(0, BF.tel)}"]`).clear().type('+33 6 00 11 22 33')
    cy.get(`input[name="${n(0, BF.color)}"]`).invoke('val', '#1a2b3c').trigger('change', { force: true })
    cy.get(`input[name="${n(0, BF.date)}"]`).invoke('val', '07/04/2025').trigger('change', { force: true })
    cy.get(`input[name="${n(0, BF.toggle)}"]`).check()
    cy.get(`input[name="${n(0, BF.checkbox)}"]`).check()
    cy.get(`input[name="${n(0, BF.yesno)}"][value="yes"]`).check()
    cy.get(`input[name="${n(0, BF.radios)}[]"][value="v2"]`).check()
    cy.get(`select[name="${n(0, BF.select)}"]`).select('v3')
    cy.get(`input[name="${n(0, BF.checkboxes)}[]"][value="v1"]`).check()
    cy.get(`input[name="${n(0, BF.checkboxes)}[]"][value="v3"]`).check()

    cy.publishPost()

    cy.get(`input[name="${n(0, BF.text)}"]`).should('have.value', 'BundleText')
    cy.get(`textarea[name="${n(0, BF.textarea)}"]`).should('have.value', 'Bundle textarea')
    cy.get(`input[name="${n(0, BF.qty)}"]`).should('have.value', '7')
    cy.get(`input[name="${n(0, BF.range)}"]`).should('have.value', '80')
    cy.get(`input[name="${n(0, BF.email)}"]`).should('have.value', 'bundle@cypress.io')
    cy.get(`input[name="${n(0, BF.url)}"]`).should('have.value', 'https://bundle.cypress.io')
    cy.get(`input[name="${n(0, BF.tel)}"]`).should('have.value', '+33 6 00 11 22 33')
    cy.get(`input[name="${n(0, BF.color)}"]`).should('have.value', '#1a2b3c')
    cy.get(`input[name="${n(0, BF.date)}"]`).should('have.value', '07/04/2025')
    cy.get(`input[name="${n(0, BF.toggle)}"]`).should('be.checked')
    cy.get(`input[name="${n(0, BF.checkbox)}"]`).should('be.checked')
    cy.get(`input[name="${n(0, BF.yesno)}"][value="yes"]`).should('be.checked')
    cy.get(`input[name="${n(0, BF.radios)}[]"][value="v2"]`).should('be.checked')
    cy.get(`input[name="${n(0, BF.radios)}[]"][value="v1"]`).should('not.be.checked')
    cy.get(`select[name="${n(0, BF.select)}"]`).should('have.value', 'v3')
    cy.get(`input[name="${n(0, BF.checkboxes)}[]"][value="v1"]`).should('be.checked')
    cy.get(`input[name="${n(0, BF.checkboxes)}[]"][value="v3"]`).should('be.checked')
    cy.get(`input[name="${n(0, BF.checkboxes)}[]"][value="v2"]`).should('not.be.checked')
  })

  it('saves and restores two bundle rows', () => {
    cy.setPostTitle('Cypress — Bundle Two Rows')

    cy.get(`input[name="${n(0, BF.text)}"]`).clear().type('Première ligne')
    cy.get(`select[name="${n(0, BF.select)}"]`).select('v1')

    cy.get(ADD).click()
    cy.get(`input[name="${n(1, BF.text)}"]`).clear().type('Deuxième ligne')
    cy.get(`select[name="${n(1, BF.select)}"]`).select('v2')

    cy.publishPost()

    cy.get(ITEMS).should('have.length', 2)
    cy.get(`input[name="${n(0, BF.text)}"]`).should('have.value', 'Première ligne')
    cy.get(`input[name="${n(1, BF.text)}"]`).should('have.value', 'Deuxième ligne')
  })

  it('shows a notice-error banner when bundle required fields are empty', () => {
    cy.setPostTitle('Cypress — Bundle Validation')
    cy.get(`input[name="${n(0, BF.text)}"]`).clear()
    cy.get('#publish').click()
    cy.get('.notice-error').should('exist')
    cy.get('tr.cfdev-has-error').should('exist')
  })
})
