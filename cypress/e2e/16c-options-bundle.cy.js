/**
 * Options pages — 16c : Layout bundle (cfdev_options_bundle).
 *
 * Run: npx cypress run --spec "cypress/e2e/16c-options-bundle.cy.js" --browser chrome
 */

const BUNDLE_ID = '_opt_bundle'
const BF_TEXT   = '_text_opt_bundle_text'
const nb        = (row, fieldId) => `cfdev[${BUNDLE_ID}][${row}][${fieldId}]`

function submitOptions() { cy.get('#submit').click() }
function expectSaved() { cy.get('.notice-success', { timeout: 15000 }).should('exist') }

describe('CFDev — Options bundle (cfdev_options_bundle)', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/admin.php?page=cfdev-cfdev_options_bundle')
  })

  it('affiche le bundle avec son bouton "Ajouter"', () => {
    cy.get('.js-cfdev-add-bundle').should('be.visible')
  })

  it('ajouter une ligne puis supprimer revient au nombre initial', () => {
    cy.get('.js-cfdev-sortable-item').its('length').then((initial) => {
      cy.get('.js-cfdev-add-bundle').click()
      cy.get('.js-cfdev-sortable-item').should('have.length', initial + 1)
      cy.get('.js-cfdev-remove-sortable').last().click()
      cy.get('.js-cfdev-sortable-item').should('have.length', initial)
    })
  })

  it('sauvegarde et restaure le texte d\'une ligne de bundle', () => {
    const val = 'BundleOptRow' + Date.now()
    cy.get(`input[name="${nb(0, BF_TEXT)}"]`).clear().type(val)
    submitOptions()
    expectSaved()
    cy.get(`input[name="${nb(0, BF_TEXT)}"]`).should('have.value', val)
  })

  it('sauvegarde plusieurs lignes', () => {
    Cypress._.times(10, () => {
      cy.get('body').then(($b) => {
        if ($b.find('.js-cfdev-remove-sortable').length > 0) {
          cy.get('.js-cfdev-remove-sortable').first().click()
        }
      })
    })
    cy.get('.js-cfdev-add-bundle').click()
    cy.get('.js-cfdev-sortable-item').should('have.length', 2)
    cy.get('.js-cfdev-sortable-item').eq(0).find(`input[name*="${BF_TEXT}"]`).clear().type('Ligne0')
    cy.get('.js-cfdev-sortable-item').eq(1).find(`input[name*="${BF_TEXT}"]`).clear().type('Ligne1')
    submitOptions()
    expectSaved()
    cy.get('.js-cfdev-sortable-item').eq(0).find(`input[name*="${BF_TEXT}"]`).should('have.value', 'Ligne0')
    cy.get('.js-cfdev-sortable-item').eq(1).find(`input[name*="${BF_TEXT}"]`).should('have.value', 'Ligne1')
  })
})