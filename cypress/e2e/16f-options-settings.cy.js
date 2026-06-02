/**
 * Options pages — 16f : Sous-menu Réglages WP (cfdev_options_reglages).
 *
 * Run: npx cypress run --spec "cypress/e2e/16f-options-settings.cy.js" --browser chrome
 */

const RGL_NAME    = 'cfdev[_opt_rgl_site_name]'
const RGL_TAGLINE = 'cfdev[_opt_rgl_tagline]'

function submitOptions() { cy.get('#submit').click() }
function expectSaved() { cy.get('.notice-success', { timeout: 15000 }).should('contain', 'Settings saved') }

describe('CFDev — Options sous Réglages (cfdev_options_reglages)', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/options-general.php?page=cfdev-cfdev_options_reglages')
  })

  it('affiche le titre et le formulaire', () => {
    cy.get('.wrap h1').should('contain', '[DEMO] Options')
    cy.get('form').should('exist')
    cy.get(`input[name="${RGL_NAME}"]`).should('exist')
  })

  it('sauvegarde et restaure les champs plats', () => {
    const name    = 'Agence Cypress ' + Date.now()
    const tagline = 'Tagline ' + Date.now()
    cy.get(`input[name="${RGL_NAME}"]`).clear().type(name)
    cy.get(`input[name="${RGL_TAGLINE}"]`).clear().type(tagline)
    submitOptions()
    expectSaved()
    cy.get(`input[name="${RGL_NAME}"]`).should('have.value', name)
    cy.get(`input[name="${RGL_TAGLINE}"]`).should('have.value', tagline)
  })

  it('affiche une erreur si le champ requis est vide', () => {
    cy.get(`input[name="${RGL_NAME}"]`).clear()
    submitOptions()
    cy.get('.notice-error, .cfdev-field-error', { timeout: 15000 }).should('exist')
  })

  it('l\'URL après sauvegarde reste sous options-general.php', () => {
    cy.get(`input[name="${RGL_NAME}"]`).clear().type('TestURL')
    submitOptions()
    cy.url({ timeout: 15000 }).should('include', 'options-general.php')
    cy.url().should('include', 'cfdev-updated=1')
  })
})