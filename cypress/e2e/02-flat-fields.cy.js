/**
 * Tests the CFDev demo flat meta box on Post edit screen.
 * Meta box:  cfdev_demo_flat  ("[DEMO] Tous les champs")
 * Fields:    generateArrayAllField('demo', 'flat')
 */

const F = {
  text:        'cfdev[_text_demo_flat_text]',
  textarea:    'cfdev[_text_demo_flat_textarea]',
  qty:         'cfdev[_text_demo_flat_qty]',
  rate:        'cfdev[_text_demo_flat_rate]',
  range:       'cfdev[_text_demo_flat_range]',
  email:       'cfdev[_text_demo_flat_email]',
  url:         'cfdev[_text_demo_flat_website]',
  tel:         'cfdev[_text_demo_flat_phone]',
  toggle:      'cfdev[_text_demo_flat_toggle]',
  checkbox:    'cfdev[_text_demo_flat_checkbox]',
  checkboxes:  'cfdev[_text_demo_flat_checkboxes][]',
  radios:      'cfdev[_text_demo_flat_radios][]',
  yesno:       'cfdev[_text_demo_flat_yesno]',
  select:      'cfdev[_text_demo_flat_select]',
  color:       'cfdev[_text_demo_flat_color]',
  date:        'cfdev[_date_demo_flat_date]',
}

describe('CFDev — Flat Fields (Post)', () => {
  beforeEach(() => {
    cy.loginToWP()
  })

  it('shows the CFDev demo flat meta box', () => {
    cy.visit('/wp-admin/post-new.php')
    cy.get('#cfdev_demo_flat').should('exist')
    cy.get('#cfdev_demo_flat h2').should('contain', 'DEMO')
  })

  it('saves and restores all flat fields', () => {
    cy.visit('/wp-admin/post-new.php')
    cy.setPostTitle('Cypress — All Flat Fields')
    cy.expandPostbox('cfdev_demo_flat')

    cy.get(`input[name="${F.text}"]`).clear().type('HelloCypress')
    cy.get(`textarea[name="${F.textarea}"]`).clear().type('Texte multi-ligne cypress')
    cy.get(`input[name="${F.qty}"]`).clear().type('42')
    cy.get(`input[name="${F.rate}"]`).clear().type('0.75')
    cy.get(`input[name="${F.range}"]`).invoke('val', '60').trigger('change')
    cy.get(`input[name="${F.email}"]`).clear().type('test@cypress.io')
    cy.get(`input[name="${F.url}"]`).clear().type('https://cypress.io')
    cy.get(`input[name="${F.tel}"]`).clear().type('+33 6 12 34 56 78')
    cy.get(`input[name="${F.color}"]`).invoke('val', '#ff5500').trigger('change', { force: true })
    cy.get(`input[name="${F.date}"]`).invoke('val', '06/15/2025').trigger('change', { force: true })
    cy.get(`input[name="${F.toggle}"]`).check()
    cy.get(`input[name="${F.checkbox}"]`).check()
    cy.get(`input[name="${F.yesno}"][value="yes"]`).check()
    cy.get(`input[name="${F.radios}"][value="v3"]`).check()
    cy.get(`select[name="${F.select}"]`).select('v2')
    cy.get(`input[name="${F.checkboxes}"][value="v1"]`).check()
    cy.get(`input[name="${F.checkboxes}"][value="v2"]`).check()

    cy.publishPost()

    cy.get(`input[name="${F.text}"]`).should('have.value', 'HelloCypress')
    cy.get(`textarea[name="${F.textarea}"]`).should('have.value', 'Texte multi-ligne cypress')
    cy.get(`input[name="${F.qty}"]`).should('have.value', '42')
    cy.get(`input[name="${F.rate}"]`).should('have.value', '0.75')
    cy.get(`input[name="${F.range}"]`).should('have.value', '60')
    cy.get(`input[name="${F.email}"]`).should('have.value', 'test@cypress.io')
    cy.get(`input[name="${F.url}"]`).should('have.value', 'https://cypress.io')
    cy.get(`input[name="${F.tel}"]`).should('have.value', '+33 6 12 34 56 78')
    cy.get(`input[name="${F.color}"]`).should('have.value', '#ff5500')
    cy.get(`input[name="${F.date}"]`).should('have.value', '06/15/2025')
    cy.get(`input[name="${F.toggle}"]`).should('be.checked')
    cy.get(`input[name="${F.checkbox}"]`).should('be.checked')
    cy.get(`input[name="${F.yesno}"][value="yes"]`).should('be.checked')
    cy.get(`input[name="${F.radios}"][value="v3"]`).should('be.checked')
    cy.get(`input[name="${F.radios}"][value="v1"]`).should('not.be.checked')
    cy.get(`select[name="${F.select}"]`).should('have.value', 'v2')
    cy.get(`input[name="${F.checkboxes}"][value="v1"]`).should('be.checked')
    cy.get(`input[name="${F.checkboxes}"][value="v2"]`).should('be.checked')
    cy.get(`input[name="${F.checkboxes}"][value="v3"]`).should('not.be.checked')
  })
})
