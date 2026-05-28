/**
 * Tests CFDev Tabs meta box on Post.
 *
 * Meta box:  cfdev_cypress_tabs  ("[CYPRESS] Tabs")
 * Tab A 'Champs plats' → Str::beautify → 'Champs Plats'
 * Tab B 'Bundle' — bundle ID = _cfdev_cypress_tabs (buildId prepends _)
 *
 * Tab A: generateArrayAllField('cypress', 'tab_a')
 * Tab B: ['bundle', generateArrayAllField('cypress', 'tab_bundle')]
 *
 * Bundle fields are in a hidden panel until the tab is clicked.
 */

const TA = {
  text:       'cfdev[_text_cypress_tab_a_text]',
  textarea:   'cfdev[_text_cypress_tab_a_textarea]',
  qty:        'cfdev[_text_cypress_tab_a_qty]',
  range:      'cfdev[_text_cypress_tab_a_range]',
  email:      'cfdev[_text_cypress_tab_a_email]',
  url:        'cfdev[_text_cypress_tab_a_website]',
  tel:        'cfdev[_text_cypress_tab_a_phone]',
  toggle:     'cfdev[_text_cypress_tab_a_toggle]',
  checkbox:   'cfdev[_text_cypress_tab_a_checkbox]',
  checkboxes: 'cfdev[_text_cypress_tab_a_checkboxes][]',
  radios:     'cfdev[_text_cypress_tab_a_radios][]',
  yesno:      'cfdev[_text_cypress_tab_a_yesno]',
  select:     'cfdev[_text_cypress_tab_a_select]',
  color:      'cfdev[_text_cypress_tab_a_color]',
  date:       'cfdev[_date_cypress_tab_a_date]',
}

const TABS_BUNDLE = '_cfdev_cypress_tabs'
const TB = {
  text:       '_text_cypress_tab_bundle_text',
  textarea:   '_text_cypress_tab_bundle_textarea',
  qty:        '_text_cypress_tab_bundle_qty',
  range:      '_text_cypress_tab_bundle_range',
  email:      '_text_cypress_tab_bundle_email',
  url:        '_text_cypress_tab_bundle_website',
  tel:        '_text_cypress_tab_bundle_phone',
  toggle:     '_text_cypress_tab_bundle_toggle',
  checkbox:   '_text_cypress_tab_bundle_checkbox',
  checkboxes: '_text_cypress_tab_bundle_checkboxes',
  radios:     '_text_cypress_tab_bundle_radios',
  yesno:      '_text_cypress_tab_bundle_yesno',
  select:     '_text_cypress_tab_bundle_select',
  color:      '_text_cypress_tab_bundle_color',
  date:       '_date_cypress_tab_bundle_date',
}

const nb = (row, fieldId) => `cfdev[${TABS_BUNDLE}][${row}][${fieldId}]`

const switchToBundle = () => {
  cy.get('#cfdev_cypress_tabs').within(() => {
    cy.contains('a', 'Bundle').click()
  })
}

describe('CFDev — Tabs Fields (Post)', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/post-new.php')
    cy.expandPostbox('cfdev_cypress_tabs')
  })

  it('shows tabs meta box with correct tab navigation', () => {
    cy.get('#cfdev_cypress_tabs').should('exist')
    cy.get('#cfdev_cypress_tabs h2').should('contain', 'Tabs')
    cy.get('#cfdev_cypress_tabs').within(() => {
      cy.get('.cfdev-tabs ul').should('contain', 'Champs Plats')
      cy.get('.cfdev-tabs ul').should('contain', 'Bundle')
    })
  })

  it('saves and restores all Tab A flat fields', () => {
    cy.setPostTitle('Cypress — Tabs All Flat')

    cy.get(`input[name="${TA.text}"]`).clear().type('TabText')
    cy.get(`textarea[name="${TA.textarea}"]`).clear().type('Tab A textarea')
    cy.get(`input[name="${TA.qty}"]`).clear().type('99')
    cy.get(`input[name="${TA.range}"]`).invoke('val', '40').trigger('change')
    cy.get(`input[name="${TA.email}"]`).clear().type('tabs@cypress.io')
    cy.get(`input[name="${TA.url}"]`).clear().type('https://tabs.cypress.io')
    cy.get(`input[name="${TA.tel}"]`).clear().type('+33 1 23 45 67 89')
    cy.get(`input[name="${TA.color}"]`).invoke('val', '#abcdef').trigger('change', { force: true })
    cy.get(`input[name="${TA.date}"]`).invoke('val', '03/20/2026').trigger('change', { force: true })
    cy.get(`input[name="${TA.toggle}"]`).check()
    cy.get(`input[name="${TA.checkbox}"]`).check()
    cy.get(`input[name="${TA.yesno}"][value="yes"]`).check()
    cy.get(`input[name="${TA.radios}"][value="v1"]`).check()
    cy.get(`select[name="${TA.select}"]`).select('v1')
    cy.get(`input[name="${TA.checkboxes}"][value="v2"]`).check()
    cy.get(`input[name="${TA.checkboxes}"][value="v3"]`).check()

    cy.publishPost()

    cy.get(`input[name="${TA.text}"]`).should('have.value', 'TabText')
    cy.get(`textarea[name="${TA.textarea}"]`).should('have.value', 'Tab A textarea')
    cy.get(`input[name="${TA.qty}"]`).should('have.value', '99')
    cy.get(`input[name="${TA.range}"]`).should('have.value', '40')
    cy.get(`input[name="${TA.email}"]`).should('have.value', 'tabs@cypress.io')
    cy.get(`input[name="${TA.url}"]`).should('have.value', 'https://tabs.cypress.io')
    cy.get(`input[name="${TA.tel}"]`).should('have.value', '+33 1 23 45 67 89')
    cy.get(`input[name="${TA.color}"]`).should('have.value', '#abcdef')
    cy.get(`input[name="${TA.date}"]`).should('have.value', '03/20/2026')
    cy.get(`input[name="${TA.toggle}"]`).should('be.checked')
    cy.get(`input[name="${TA.checkbox}"]`).should('be.checked')
    cy.get(`input[name="${TA.yesno}"][value="yes"]`).should('be.checked')
    cy.get(`input[name="${TA.radios}"][value="v1"]`).should('be.checked')
    cy.get(`input[name="${TA.radios}"][value="v2"]`).should('not.be.checked')
    cy.get(`select[name="${TA.select}"]`).should('have.value', 'v1')
    cy.get(`input[name="${TA.checkboxes}"][value="v2"]`).should('be.checked')
    cy.get(`input[name="${TA.checkboxes}"][value="v3"]`).should('be.checked')
    cy.get(`input[name="${TA.checkboxes}"][value="v1"]`).should('not.be.checked')
  })

  it('saves and restores all Tab B bundle fields in row 0', () => {
    cy.setPostTitle('Cypress — Tabs Bundle All Fields')
    switchToBundle()

    cy.get(`input[name="${nb(0, TB.text)}"]`).clear().type('TabBundleText')
    cy.get(`textarea[name="${nb(0, TB.textarea)}"]`).clear().type('Tab bundle textarea')
    cy.get(`input[name="${nb(0, TB.qty)}"]`).clear().type('42')
    cy.get(`input[name="${nb(0, TB.range)}"]`).invoke('val', '60').trigger('change')
    cy.get(`input[name="${nb(0, TB.email)}"]`).clear().type('tabBundle@cypress.io')
    cy.get(`input[name="${nb(0, TB.url)}"]`).clear().type('https://tabBundle.cypress.io')
    cy.get(`input[name="${nb(0, TB.tel)}"]`).clear().type('+33 5 11 22 33 44')
    cy.get(`input[name="${nb(0, TB.color)}"]`).invoke('val', '#aabbcc').trigger('change', { force: true })
    cy.get(`input[name="${nb(0, TB.date)}"]`).invoke('val', '06/15/2026').trigger('change', { force: true })
    cy.get(`input[name="${nb(0, TB.toggle)}"]`).check()
    cy.get(`input[name="${nb(0, TB.checkbox)}"]`).check()
    cy.get(`input[name="${nb(0, TB.yesno)}"][value="yes"]`).check()
    cy.get(`input[name="${nb(0, TB.radios)}[]"][value="v2"]`).check()
    cy.get(`select[name="${nb(0, TB.select)}"]`).select('v2')
    cy.get(`input[name="${nb(0, TB.checkboxes)}[]"][value="v1"]`).check()
    cy.get(`input[name="${nb(0, TB.checkboxes)}[]"][value="v3"]`).check()

    cy.publishPost()
    switchToBundle()

    cy.get(`input[name="${nb(0, TB.text)}"]`).should('have.value', 'TabBundleText')
    cy.get(`textarea[name="${nb(0, TB.textarea)}"]`).should('have.value', 'Tab bundle textarea')
    cy.get(`input[name="${nb(0, TB.qty)}"]`).should('have.value', '42')
    cy.get(`input[name="${nb(0, TB.range)}"]`).should('have.value', '60')
    cy.get(`input[name="${nb(0, TB.email)}"]`).should('have.value', 'tabBundle@cypress.io')
    cy.get(`input[name="${nb(0, TB.url)}"]`).should('have.value', 'https://tabBundle.cypress.io')
    cy.get(`input[name="${nb(0, TB.tel)}"]`).should('have.value', '+33 5 11 22 33 44')
    cy.get(`input[name="${nb(0, TB.color)}"]`).should('have.value', '#aabbcc')
    cy.get(`input[name="${nb(0, TB.date)}"]`).should('have.value', '06/15/2026')
    cy.get(`input[name="${nb(0, TB.toggle)}"]`).should('be.checked')
    cy.get(`input[name="${nb(0, TB.checkbox)}"]`).should('be.checked')
    cy.get(`input[name="${nb(0, TB.yesno)}"][value="yes"]`).should('be.checked')
    cy.get(`input[name="${nb(0, TB.radios)}[]"][value="v2"]`).should('be.checked')
    cy.get(`input[name="${nb(0, TB.radios)}[]"][value="v1"]`).should('not.be.checked')
    cy.get(`select[name="${nb(0, TB.select)}"]`).should('have.value', 'v2')
    cy.get(`input[name="${nb(0, TB.checkboxes)}[]"][value="v1"]`).should('be.checked')
    cy.get(`input[name="${nb(0, TB.checkboxes)}[]"][value="v3"]`).should('be.checked')
    cy.get(`input[name="${nb(0, TB.checkboxes)}[]"][value="v2"]`).should('not.be.checked')
  })

  it('adds a row in Tab B bundle and saves both rows', () => {
    cy.setPostTitle('Cypress — Tabs Bundle Two Rows')
    switchToBundle()
    cy.get(`input[name="${nb(0, TB.text)}"]`).clear().type('Row zéro')
    cy.get(`select[name="${nb(0, TB.select)}"]`).select('v1')
    cy.get(`#${TABS_BUNDLE} .js-cfdev-add-bundle`).click()
    cy.get(`input[name="${nb(1, TB.text)}"]`).clear().type('Row un')
    cy.get(`select[name="${nb(1, TB.select)}"]`).select('v2')
    cy.publishPost()
    switchToBundle()
    cy.get(`#${TABS_BUNDLE} .js-cfdev-sortable-item`).should('have.length', 2)
    cy.get(`input[name="${nb(0, TB.text)}"]`).should('have.value', 'Row zéro')
    cy.get(`input[name="${nb(1, TB.text)}"]`).should('have.value', 'Row un')
  })
})
