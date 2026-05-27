/**
 * Tests CFDev Tabs meta box on Page (requires template-home.php).
 *
 * Meta box:  cfdev_demo_tabs  ("[DEMO] Tabs")
 * Condition: ->onlyForTemplate('template-home.php')
 *
 * matchesConditions() reads _wp_page_template only AFTER a first save, so
 * createPageWithTemplate() publishes once to set the template; then we fill
 * fields and call cy.publishPost() to save them.
 *
 * Tab A: generateArrayAllField('demo', 'tab_a')
 * Tab B: fixed file + image fields (skipped — media library / iframes)
 */

const TF = {
  text:       'cfdev[_text_demo_tab_a_text]',
  textarea:   'cfdev[_text_demo_tab_a_textarea]',
  qty:        'cfdev[_text_demo_tab_a_qty]',
  range:      'cfdev[_text_demo_tab_a_range]',
  email:      'cfdev[_text_demo_tab_a_email]',
  url:        'cfdev[_text_demo_tab_a_website]',
  tel:        'cfdev[_text_demo_tab_a_phone]',
  toggle:     'cfdev[_text_demo_tab_a_toggle]',
  checkbox:   'cfdev[_text_demo_tab_a_checkbox]',
  checkboxes: 'cfdev[_text_demo_tab_a_checkboxes][]',
  radios:     'cfdev[_text_demo_tab_a_radios][]',
  yesno:      'cfdev[_text_demo_tab_a_yesno]',
  select:     'cfdev[_text_demo_tab_a_select]',
  color:      'cfdev[_text_demo_tab_a_color]',
  date:       'cfdev[_date_demo_tab_a_date]',
}

const createPageWithTemplate = (title) => {
  cy.visit('/wp-admin/post-new.php?post_type=page')
  cy.setPostTitle(title)
  cy.get('#page_template').select('template-home.php')
  cy.get('#publish').click()
  cy.get('#message.notice-success, .notice-success').should('exist')
}

describe('CFDev — Tabs Fields (Page + template-home.php)', () => {
  beforeEach(() => {
    cy.loginToWP()
  })

  it('shows tabs meta box and navigates between tabs', () => {
    createPageWithTemplate('Cypress — Page Tabs Structure')
    cy.expandPostbox('cfdev_demo_tabs')
    cy.get('#cfdev_demo_tabs').should('exist')
    cy.get('#cfdev_demo_tabs h2').should('contain', 'Tabs')
    cy.get('#cfdev_demo_tabs').within(() => {
      cy.get('.cfdev-tabs ul').should('contain', 'Onglet A')
      cy.get('.cfdev-tabs ul').should('contain', 'Onglet B')
      cy.contains('a', 'Onglet B').click()
    })
  })

  it('saves and restores all Tab A fields', () => {
    createPageWithTemplate('Cypress — Page Tabs All Fields')
    cy.expandPostbox('cfdev_demo_tabs')

    cy.get(`input[name="${TF.text}"]`).clear().type('TabAText')
    cy.get(`textarea[name="${TF.textarea}"]`).clear().type('Tab A textarea')
    cy.get(`input[name="${TF.qty}"]`).clear().type('99')
    cy.get(`input[name="${TF.range}"]`).invoke('val', '40').trigger('change')
    cy.get(`input[name="${TF.email}"]`).clear().type('tabs@cypress.io')
    cy.get(`input[name="${TF.url}"]`).clear().type('https://tabs.cypress.io')
    cy.get(`input[name="${TF.tel}"]`).clear().type('+33 1 23 45 67 89')
    cy.get(`input[name="${TF.color}"]`).invoke('val', '#abcdef').trigger('change', { force: true })
    cy.get(`input[name="${TF.date}"]`).invoke('val', '03/20/2026').trigger('change', { force: true })
    cy.get(`input[name="${TF.toggle}"]`).check()
    cy.get(`input[name="${TF.checkbox}"]`).check()
    cy.get(`input[name="${TF.yesno}"][value="yes"]`).check()
    cy.get(`input[name="${TF.radios}"][value="v1"]`).check()
    cy.get(`select[name="${TF.select}"]`).select('v1')
    cy.get(`input[name="${TF.checkboxes}"][value="v2"]`).check()
    cy.get(`input[name="${TF.checkboxes}"][value="v3"]`).check()

    cy.publishPost()

    cy.get(`input[name="${TF.text}"]`).should('have.value', 'TabAText')
    cy.get(`textarea[name="${TF.textarea}"]`).should('have.value', 'Tab A textarea')
    cy.get(`input[name="${TF.qty}"]`).should('have.value', '99')
    cy.get(`input[name="${TF.range}"]`).should('have.value', '40')
    cy.get(`input[name="${TF.email}"]`).should('have.value', 'tabs@cypress.io')
    cy.get(`input[name="${TF.url}"]`).should('have.value', 'https://tabs.cypress.io')
    cy.get(`input[name="${TF.tel}"]`).should('have.value', '+33 1 23 45 67 89')
    cy.get(`input[name="${TF.color}"]`).should('have.value', '#abcdef')
    cy.get(`input[name="${TF.date}"]`).should('have.value', '03/20/2026')
    cy.get(`input[name="${TF.toggle}"]`).should('be.checked')
    cy.get(`input[name="${TF.checkbox}"]`).should('be.checked')
    cy.get(`input[name="${TF.yesno}"][value="yes"]`).should('be.checked')
    cy.get(`input[name="${TF.radios}"][value="v1"]`).should('be.checked')
    cy.get(`input[name="${TF.radios}"][value="v2"]`).should('not.be.checked')
    cy.get(`select[name="${TF.select}"]`).should('have.value', 'v1')
    cy.get(`input[name="${TF.checkboxes}"][value="v2"]`).should('be.checked')
    cy.get(`input[name="${TF.checkboxes}"][value="v3"]`).should('be.checked')
    cy.get(`input[name="${TF.checkboxes}"][value="v1"]`).should('not.be.checked')
  })
})
