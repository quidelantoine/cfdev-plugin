/**
 * Tests CFDev Accordion meta box on Post.
 *
 * Meta box:  cfdev_cypress_accordion  ("[CYPRESS] Accordéon")
 * Section A 'Champs plats' → Str::beautify → 'Champs Plats' (expanded by default)
 * Section B 'Bundle' — bundle ID = _cfdev_cypress_accordion (buildId prepends _)
 *
 * Section A: generateArrayAllField('cypress', 'acc_a')
 * Section B: ['bundle', generateArrayAllField('cypress', 'acc_bundle')]
 *
 * Bundle fields are in a collapsed section; expandBundle() must be called first.
 */

const AA = {
  text:       'cfdev[_text_cypress_acc_a_text]',
  textarea:   'cfdev[_text_cypress_acc_a_textarea]',
  qty:        'cfdev[_text_cypress_acc_a_qty]',
  range:      'cfdev[_text_cypress_acc_a_range]',
  email:      'cfdev[_text_cypress_acc_a_email]',
  url:        'cfdev[_text_cypress_acc_a_website]',
  tel:        'cfdev[_text_cypress_acc_a_phone]',
  toggle:     'cfdev[_text_cypress_acc_a_toggle]',
  checkbox:   'cfdev[_text_cypress_acc_a_checkbox]',
  checkboxes: 'cfdev[_text_cypress_acc_a_checkboxes][]',
  radios:     'cfdev[_text_cypress_acc_a_radios][]',
  yesno:      'cfdev[_text_cypress_acc_a_yesno]',
  select:     'cfdev[_text_cypress_acc_a_select]',
  color:      'cfdev[_text_cypress_acc_a_color]',
  date:       'cfdev[_date_cypress_acc_a_date]',
}

const ACC_BUNDLE = '_cfdev_cypress_accordion'
const AB = {
  text:       '_text_cypress_acc_bundle_text',
  textarea:   '_text_cypress_acc_bundle_textarea',
  qty:        '_text_cypress_acc_bundle_qty',
  range:      '_text_cypress_acc_bundle_range',
  email:      '_text_cypress_acc_bundle_email',
  url:        '_text_cypress_acc_bundle_website',
  tel:        '_text_cypress_acc_bundle_phone',
  toggle:     '_text_cypress_acc_bundle_toggle',
  checkbox:   '_text_cypress_acc_bundle_checkbox',
  checkboxes: '_text_cypress_acc_bundle_checkboxes',
  radios:     '_text_cypress_acc_bundle_radios',
  yesno:      '_text_cypress_acc_bundle_yesno',
  select:     '_text_cypress_acc_bundle_select',
  color:      '_text_cypress_acc_bundle_color',
  date:       '_date_cypress_acc_bundle_date',
}

const nb = (row, fieldId) => `cfdev[${ACC_BUNDLE}][${row}][${fieldId}]`

const expandBundle = () => {
  cy.get('#cfdev_cypress_accordion').within(() => {
    cy.contains('h3', 'Bundle').click()
  })
}

describe('CFDev — Accordion Fields (Post)', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/post-new.php')
  })

  it('shows accordion meta box with section headers', () => {
    cy.get('#cfdev_cypress_accordion').should('exist')
    cy.get('#cfdev_cypress_accordion h2').should('contain', 'Accordéon')
    cy.get('#cfdev_cypress_accordion .js-cfdev-accordion').within(() => {
      cy.contains('h3', 'Champs Plats').should('exist')
      cy.contains('h3', 'Bundle').should('exist')
    })
  })

  it('saves and restores all Section A flat fields', () => {
    cy.setPostTitle('Cypress — Accordion All Flat')

    cy.get(`input[name="${AA.text}"]`).clear().type('AccordionText')
    cy.get(`textarea[name="${AA.textarea}"]`).clear().type('Accordion textarea')
    cy.get(`input[name="${AA.qty}"]`).clear().type('55')
    cy.get(`input[name="${AA.range}"]`).invoke('val', '30').trigger('change')
    cy.get(`input[name="${AA.email}"]`).clear().type('accordion@cypress.io')
    cy.get(`input[name="${AA.url}"]`).clear().type('https://accordion.cypress.io')
    cy.get(`input[name="${AA.tel}"]`).clear().type('+33 4 56 78 90 12')
    cy.get(`input[name="${AA.color}"]`).invoke('val', '#fedcba').trigger('change', { force: true })
    cy.get(`input[name="${AA.date}"]`).invoke('val', '12/31/2025').trigger('change', { force: true })
    cy.get(`input[name="${AA.toggle}"]`).check()
    cy.get(`input[name="${AA.checkbox}"]`).check()
    cy.get(`input[name="${AA.yesno}"][value="yes"]`).check()
    cy.get(`input[name="${AA.radios}"][value="v2"]`).check()
    cy.get(`select[name="${AA.select}"]`).select('v3')
    cy.get(`input[name="${AA.checkboxes}"][value="v1"]`).check()
    cy.get(`input[name="${AA.checkboxes}"][value="v3"]`).check()

    cy.publishPost()

    cy.get(`input[name="${AA.text}"]`).should('have.value', 'AccordionText')
    cy.get(`textarea[name="${AA.textarea}"]`).should('have.value', 'Accordion textarea')
    cy.get(`input[name="${AA.qty}"]`).should('have.value', '55')
    cy.get(`input[name="${AA.range}"]`).should('have.value', '30')
    cy.get(`input[name="${AA.email}"]`).should('have.value', 'accordion@cypress.io')
    cy.get(`input[name="${AA.url}"]`).should('have.value', 'https://accordion.cypress.io')
    cy.get(`input[name="${AA.tel}"]`).should('have.value', '+33 4 56 78 90 12')
    cy.get(`input[name="${AA.color}"]`).should('have.value', '#fedcba')
    cy.get(`input[name="${AA.date}"]`).should('have.value', '12/31/2025')
    cy.get(`input[name="${AA.toggle}"]`).should('be.checked')
    cy.get(`input[name="${AA.checkbox}"]`).should('be.checked')
    cy.get(`input[name="${AA.yesno}"][value="yes"]`).should('be.checked')
    cy.get(`input[name="${AA.radios}"][value="v2"]`).should('be.checked')
    cy.get(`input[name="${AA.radios}"][value="v1"]`).should('not.be.checked')
    cy.get(`select[name="${AA.select}"]`).should('have.value', 'v3')
    cy.get(`input[name="${AA.checkboxes}"][value="v1"]`).should('be.checked')
    cy.get(`input[name="${AA.checkboxes}"][value="v3"]`).should('be.checked')
    cy.get(`input[name="${AA.checkboxes}"][value="v2"]`).should('not.be.checked')
  })

  it('saves and restores all Section B bundle fields in row 0', () => {
    cy.setPostTitle('Cypress — Accordion Bundle All Fields')
    expandBundle()

    cy.get(`input[name="${nb(0, AB.text)}"]`).clear().type('AccBundleText')
    cy.get(`textarea[name="${nb(0, AB.textarea)}"]`).clear().type('Acc bundle textarea')
    cy.get(`input[name="${nb(0, AB.qty)}"]`).clear().type('11')
    cy.get(`input[name="${nb(0, AB.range)}"]`).invoke('val', '70').trigger('change')
    cy.get(`input[name="${nb(0, AB.email)}"]`).clear().type('accBundle@cypress.io')
    cy.get(`input[name="${nb(0, AB.url)}"]`).clear().type('https://accBundle.cypress.io')
    cy.get(`input[name="${nb(0, AB.tel)}"]`).clear().type('+33 6 99 88 77 66')
    cy.get(`input[name="${nb(0, AB.color)}"]`).invoke('val', '#334455').trigger('change', { force: true })
    cy.get(`input[name="${nb(0, AB.date)}"]`).invoke('val', '09/01/2026').trigger('change', { force: true })
    cy.get(`input[name="${nb(0, AB.toggle)}"]`).check()
    cy.get(`input[name="${nb(0, AB.checkbox)}"]`).check()
    cy.get(`input[name="${nb(0, AB.yesno)}"][value="yes"]`).check()
    cy.get(`input[name="${nb(0, AB.radios)}[]"][value="v3"]`).check()
    cy.get(`select[name="${nb(0, AB.select)}"]`).select('v1')
    cy.get(`input[name="${nb(0, AB.checkboxes)}[]"][value="v2"]`).check()
    cy.get(`input[name="${nb(0, AB.checkboxes)}[]"][value="v3"]`).check()

    cy.publishPost()
    expandBundle()

    cy.get(`input[name="${nb(0, AB.text)}"]`).should('have.value', 'AccBundleText')
    cy.get(`textarea[name="${nb(0, AB.textarea)}"]`).should('have.value', 'Acc bundle textarea')
    cy.get(`input[name="${nb(0, AB.qty)}"]`).should('have.value', '11')
    cy.get(`input[name="${nb(0, AB.range)}"]`).should('have.value', '70')
    cy.get(`input[name="${nb(0, AB.email)}"]`).should('have.value', 'accBundle@cypress.io')
    cy.get(`input[name="${nb(0, AB.url)}"]`).should('have.value', 'https://accBundle.cypress.io')
    cy.get(`input[name="${nb(0, AB.tel)}"]`).should('have.value', '+33 6 99 88 77 66')
    cy.get(`input[name="${nb(0, AB.color)}"]`).should('have.value', '#334455')
    cy.get(`input[name="${nb(0, AB.date)}"]`).should('have.value', '09/01/2026')
    cy.get(`input[name="${nb(0, AB.toggle)}"]`).should('be.checked')
    cy.get(`input[name="${nb(0, AB.checkbox)}"]`).should('be.checked')
    cy.get(`input[name="${nb(0, AB.yesno)}"][value="yes"]`).should('be.checked')
    cy.get(`input[name="${nb(0, AB.radios)}[]"][value="v3"]`).should('be.checked')
    cy.get(`input[name="${nb(0, AB.radios)}[]"][value="v1"]`).should('not.be.checked')
    cy.get(`select[name="${nb(0, AB.select)}"]`).should('have.value', 'v1')
    cy.get(`input[name="${nb(0, AB.checkboxes)}[]"][value="v2"]`).should('be.checked')
    cy.get(`input[name="${nb(0, AB.checkboxes)}[]"][value="v3"]`).should('be.checked')
    cy.get(`input[name="${nb(0, AB.checkboxes)}[]"][value="v1"]`).should('not.be.checked')
  })

  it('adds a row in Section B bundle and saves both rows', () => {
    cy.setPostTitle('Cypress — Accordion Bundle Two Rows')
    expandBundle()
    cy.get(`input[name="${nb(0, AB.text)}"]`).clear().type('Première ligne acc')
    cy.get(`select[name="${nb(0, AB.select)}"]`).select('v1')
    cy.get(`#${ACC_BUNDLE} .js-cfdev-add-bundle`).click()
    cy.get(`input[name="${nb(1, AB.text)}"]`).clear().type('Deuxième ligne acc')
    cy.get(`select[name="${nb(1, AB.select)}"]`).select('v2')
    cy.publishPost()
    expandBundle()
    cy.get(`#${ACC_BUNDLE} .js-cfdev-sortable-item`).should('have.length', 2)
    cy.get(`input[name="${nb(0, AB.text)}"]`).should('have.value', 'Première ligne acc')
    cy.get(`input[name="${nb(1, AB.text)}"]`).should('have.value', 'Deuxième ligne acc')
  })
})
