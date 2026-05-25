/**
 * Tests CFDev Accordion meta box on Page (requires template-home.php).
 *
 * Meta box:  cfdev_demo_accordion  ("[DEMO] Accordéon")
 * Condition: ->onlyForTemplate('template-home.php')
 *
 * matchesConditions() reads _wp_page_template only AFTER a first save, so
 * createPageWithTemplate() publishes once to set the template; then we fill
 * fields and call cy.publishPost() to save them.
 *
 * Section A: generateArrayAllField('demo', 'acc_a') — expanded by default
 * Section B: fixed title + text fields
 *
 * Skipped: image, file, wysiwyg, gallery, link, post/term/user fields
 */

const AF = {
  // Section A
  text:       'cfdev[_text_demo_acc_a_text]',
  textarea:   'cfdev[_text_demo_acc_a_textarea]',
  qty:        'cfdev[_text_demo_acc_a_qty]',
  range:      'cfdev[_text_demo_acc_a_range]',
  email:      'cfdev[_text_demo_acc_a_email]',
  url:        'cfdev[_text_demo_acc_a_website]',
  tel:        'cfdev[_text_demo_acc_a_phone]',
  toggle:     'cfdev[_text_demo_acc_a_toggle]',
  checkbox:   'cfdev[_text_demo_acc_a_checkbox]',
  checkboxes: 'cfdev[_text_demo_acc_a_checkboxes][]',
  radios:     'cfdev[_text_demo_acc_a_radios][]',
  yesno:      'cfdev[_text_demo_acc_a_yesno]',
  select:     'cfdev[_text_demo_acc_a_select]',
  color:      'cfdev[_text_demo_acc_a_color]',
  date:       'cfdev[_date_demo_acc_a_date]',
  // Section B — fixed fields
  titleB:     'cfdev[_demo_acc_b_title]',
  textB:      'cfdev[_demo_acc_b_text]',
}

const createPageWithTemplate = (title) => {
  cy.visit('/wp-admin/post-new.php?post_type=page')
  cy.setPostTitle(title)
  cy.get('#page_template').select('template-home.php')
  cy.get('#publish').click()
  cy.get('#message.notice-success, .notice-success').should('exist')
}

describe('CFDev — Accordion Fields (Page + template-home.php)', () => {
  beforeEach(() => {
    cy.loginToWP()
  })

  it('shows accordion meta box with sections and can expand/collapse', () => {
    createPageWithTemplate('Cypress — Page Accordion Structure')
    cy.get('#cfdev_demo_accordion').should('exist')
    cy.get('#cfdev_demo_accordion h2').should('contain', 'Accordéon')
    cy.get('#cfdev_demo_accordion').within(() => {
      cy.contains('Section A').should('exist')
      cy.contains('Section B').should('exist')
      // Collapse then re-expand Section A
      cy.contains('Section A').click()
      cy.contains('Section A').click()
      cy.get(`input[name="${AF.text}"]`).should('exist')
    })
  })

  it('saves and restores all Section A and Section B fields', () => {
    createPageWithTemplate('Cypress — Page Accordion All Fields')

    // Section A (expanded by default)
    cy.get(`input[name="${AF.text}"]`).clear().type('AccordionText')
    cy.get(`textarea[name="${AF.textarea}"]`).clear().type('Accordion textarea')
    cy.get(`input[name="${AF.qty}"]`).clear().type('55')
    cy.get(`input[name="${AF.range}"]`).invoke('val', '30').trigger('change')
    cy.get(`input[name="${AF.email}"]`).clear().type('accordion@cypress.io')
    cy.get(`input[name="${AF.url}"]`).clear().type('https://accordion.cypress.io')
    cy.get(`input[name="${AF.tel}"]`).clear().type('+33 4 56 78 90 12')
    cy.get(`input[name="${AF.color}"]`).invoke('val', '#fedcba').trigger('change', { force: true })
    cy.get(`input[name="${AF.date}"]`).invoke('val', '12/31/2025').trigger('change', { force: true })
    cy.get(`input[name="${AF.toggle}"]`).check()
    cy.get(`input[name="${AF.checkbox}"]`).check()
    cy.get(`input[name="${AF.yesno}"][value="yes"]`).check()
    cy.get(`input[name="${AF.radios}"][value="v2"]`).check()
    cy.get(`select[name="${AF.select}"]`).select('v3')
    cy.get(`input[name="${AF.checkboxes}"][value="v1"]`).check()
    cy.get(`input[name="${AF.checkboxes}"][value="v3"]`).check()

    // Section B fixed fields
    cy.get(`input[name="${AF.titleB}"]`).clear().type('Titre Section B')
    cy.get(`input[name="${AF.textB}"]`).clear().type('Texte Section B')

    cy.publishPost()

    cy.get(`input[name="${AF.text}"]`).should('have.value', 'AccordionText')
    cy.get(`textarea[name="${AF.textarea}"]`).should('have.value', 'Accordion textarea')
    cy.get(`input[name="${AF.qty}"]`).should('have.value', '55')
    cy.get(`input[name="${AF.range}"]`).should('have.value', '30')
    cy.get(`input[name="${AF.email}"]`).should('have.value', 'accordion@cypress.io')
    cy.get(`input[name="${AF.url}"]`).should('have.value', 'https://accordion.cypress.io')
    cy.get(`input[name="${AF.tel}"]`).should('have.value', '+33 4 56 78 90 12')
    cy.get(`input[name="${AF.color}"]`).should('have.value', '#fedcba')
    cy.get(`input[name="${AF.date}"]`).should('have.value', '12/31/2025')
    cy.get(`input[name="${AF.toggle}"]`).should('be.checked')
    cy.get(`input[name="${AF.checkbox}"]`).should('be.checked')
    cy.get(`input[name="${AF.yesno}"][value="yes"]`).should('be.checked')
    cy.get(`input[name="${AF.radios}"][value="v2"]`).should('be.checked')
    cy.get(`input[name="${AF.radios}"][value="v1"]`).should('not.be.checked')
    cy.get(`select[name="${AF.select}"]`).should('have.value', 'v3')
    cy.get(`input[name="${AF.checkboxes}"][value="v1"]`).should('be.checked')
    cy.get(`input[name="${AF.checkboxes}"][value="v3"]`).should('be.checked')
    cy.get(`input[name="${AF.checkboxes}"][value="v2"]`).should('not.be.checked')
    cy.get(`input[name="${AF.titleB}"]`).should('have.value', 'Titre Section B')
    cy.get(`input[name="${AF.textB}"]`).should('have.value', 'Texte Section B')
  })
})
