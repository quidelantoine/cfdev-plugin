/**
 * Options pages — 16d : Layout tabs (cfdev_options_tabs).
 *
 * Run: npx cypress run --spec "cypress/e2e/16d-options-tabs.cy.js" --browser chrome
 */

const TAB_TEXT   = 'cfdev[_opt_tab_a_text]'
const TAB_SELECT = 'cfdev[_opt_tab_a_select]'

function submitOptions() { cy.get('#submit').click() }
function expectSaved() { cy.get('.notice-success', { timeout: 15000 }).should('contain', 'Settings saved') }

describe('CFDev — Options tabs (cfdev_options_tabs)', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/admin.php?page=cfdev-cfdev_options_tabs')
  })

  it('affiche les deux onglets', () => {
    cy.contains('.js-cfdev-tabs li a', 'Champs Plats').should('exist')
    cy.contains('.js-cfdev-tabs li a', 'Bundle Dans Tab').should('exist')
  })

  it('bascule vers l\'onglet "Bundle dans tab" et affiche le bundle', () => {
    cy.contains('.js-cfdev-tabs li a', 'Bundle Dans Tab').click()
    cy.get('.js-cfdev-add-bundle').should('be.visible')
  })

  it('sauvegarde un champ texte dans l\'onglet "Champs plats"', () => {
    cy.contains('.js-cfdev-tabs li a', 'Champs Plats').click()
    const val = 'TabTextOpt' + Date.now()
    cy.get(`input[name="${TAB_TEXT}"]`).clear().type(val)
    submitOptions()
    expectSaved()
    cy.contains('.js-cfdev-tabs li a', 'Champs Plats').click()
    cy.get(`input[name="${TAB_TEXT}"]`).should('have.value', val)
  })

  it('sauvegarde un select dans l\'onglet "Champs plats"', () => {
    cy.contains('.js-cfdev-tabs li a', 'Champs Plats').click()
    cy.get(`select[name="${TAB_SELECT}"]`).select('v3')
    submitOptions()
    expectSaved()
    cy.contains('.js-cfdev-tabs li a', 'Champs Plats').click()
    cy.get(`select[name="${TAB_SELECT}"]`).should('have.value', 'v3')
  })
})