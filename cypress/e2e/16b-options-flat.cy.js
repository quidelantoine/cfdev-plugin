/**
 * Options pages — 16b : Page plate (cfdev_options_demo).
 *
 * Run: npx cypress run --spec "cypress/e2e/16b-options-flat.cy.js" --browser chrome
 */

const MAIN_TEXT   = 'cfdev[_text_opt_main_text]'
const MAIN_SELECT = 'cfdev[_text_opt_main_select]'
const MAIN_TOGGLE = 'cfdev[_text_opt_main_toggle]'

function submitOptions() { cy.get('#submit').click() }
function expectSaved() { cy.get('.notice-success', { timeout: 15000 }).should('contain', 'Settings saved') }

describe('CFDev — Options page plate (cfdev_options_demo)', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/admin.php?page=cfdev-cfdev_options_demo')
  })

  it('affiche le titre, la description et le formulaire', () => {
    cy.get('.wrap h1').should('contain', '[DEMO] Options')
    cy.get('.cfdev-description').should('contain', 'globaux')
    cy.get('form').should('exist')
    cy.get('#submit').should('exist')
  })

  it('sauvegarde et restaure un champ texte', () => {
    const val = 'CypressMainText' + Date.now()
    cy.get(`input[name="${MAIN_TEXT}"]`).clear().type(val)
    submitOptions()
    expectSaved()
    cy.get(`input[name="${MAIN_TEXT}"]`).should('have.value', val)
  })

  it('sauvegarde et restaure un champ select', () => {
    cy.get(`select[name="${MAIN_SELECT}"]`).select('v2')
    submitOptions()
    expectSaved()
    cy.get(`select[name="${MAIN_SELECT}"]`).should('have.value', 'v2')
  })

  it('sauvegarde et restaure un toggle', () => {
    cy.get(`input[name="${MAIN_TOGGLE}"]`).check()
    submitOptions()
    expectSaved()
    cy.get(`input[name="${MAIN_TOGGLE}"]`).should('be.checked')
  })

  it('l\'URL après sauvegarde contient cfdev-updated=1', () => {
    cy.get(`input[name="${MAIN_TEXT}"]`).clear().type('check-url')
    submitOptions()
    cy.url({ timeout: 15000 }).should('include', 'cfdev-updated=1')
  })
})