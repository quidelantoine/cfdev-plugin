/**
 * Options pages — 16e : Layout accordion + validation (cfdev_options_accordion).
 *
 * Run: npx cypress run --spec "cypress/e2e/16e-options-accordion.cy.js" --browser chrome
 */

const ACC_REQUIRED = 'cfdev[_opt_acc_site_name]'

function submitOptions() { cy.get('#submit').click() }
function expectSaved() { cy.get('.notice-success', { timeout: 15000 }).should('contain', 'Settings saved') }

describe('CFDev — Options accordion — validation', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/admin.php?page=cfdev-cfdev_options_accordion')
    cy.get('.js-cfdev-accordion h3').first().click()
  })

  it('affiche les sections accordion', () => {
    cy.get('.js-cfdev-accordion h3').should('have.length.gte', 3)
  })

  it('affiche une notice-error si le champ requis est vide', () => {
    cy.get(`input[name="${ACC_REQUIRED}"]`).clear()
    submitOptions()
    cy.get('.notice-error, .cfdev-field-error', { timeout: 15000 }).should('exist')
  })

  it('marque la ligne du champ requis avec .cfdev-has-error', () => {
    cy.get(`input[name="${ACC_REQUIRED}"]`).clear()
    submitOptions()
    cy.url({ timeout: 15000 }).should('not.include', 'cfdev-updated')
    cy.get('.js-cfdev-accordion h3').first().click()
    cy.get(`input[name="${ACC_REQUIRED}"]`)
      .closest('tr')
      .should('have.class', 'cfdev-has-error')
  })

  it('sauvegarde avec succès quand le champ requis est rempli', () => {
    cy.get(`input[name="${ACC_REQUIRED}"]`).clear().type('Mon site CFDev')
    submitOptions()
    expectSaved()
  })

  it('efface l\'erreur une fois le champ rempli et sauvegardé', () => {
    cy.get(`input[name="${ACC_REQUIRED}"]`).clear()
    submitOptions()
    cy.url({ timeout: 15000 }).should('not.include', 'cfdev-updated')

    cy.get('.js-cfdev-accordion h3').first().click()
    cy.get(`input[name="${ACC_REQUIRED}"]`).clear().type('Site corrigé')
    submitOptions()
    expectSaved()

    cy.get('.cfdev-has-error').should('not.exist')
  })
})