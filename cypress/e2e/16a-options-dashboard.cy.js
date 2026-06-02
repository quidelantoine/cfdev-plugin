/**
 * Options pages — 16a : Dashboard CFDev, onglet Options.
 *
 * Run: npx cypress run --spec "cypress/e2e/16a-options-dashboard.cy.js" --browser chrome
 */

describe('CFDev — Dashboard: onglet Options', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/admin.php?page=cfdev')
  })

  it('affiche un onglet Options dans la nav du registry', () => {
    cy.get('.cfdev-tabs-nav a[href="#cfdev-tab-options"]').should('exist')
  })

  it('le panneau Options liste au moins une page d\'options', () => {
    cy.get('a[href="#cfdev-tab-options"]').click()
    cy.get('#cfdev-tab-options').should('be.visible')
    cy.get('#cfdev-tab-options .cfdev-group').should('have.length.gte', 1)
  })

  it('les groupes option affichent un bouton Edit (pas Inspect)', () => {
    cy.get('a[href="#cfdev-tab-options"]').click()
    cy.get('#cfdev-tab-options .cfdev-group').first().within(() => {
      cy.get('.cfdev-btn-inspect').should('not.exist')
      cy.get('a.button').should('exist')
    })
  })

  it('le bouton Edit pointe vers la bonne page d\'options', () => {
    cy.get('a[href="#cfdev-tab-options"]').click()
    cy.get('#cfdev-tab-options')
      .contains('.cfdev-group-id', 'cfdev_options_demo')
      .closest('.cfdev-group')
      .find('a.button')
      .should('have.attr', 'href')
      .and('include', 'cfdev-cfdev_options_demo')
  })

  it('le compteur de l\'onglet Options correspond au nombre de groupes', () => {
    cy.get('a[href="#cfdev-tab-options"]').click()
    cy.get('#cfdev-tab-options .cfdev-group').then(($groups) => {
      const count = $groups.length
      cy.get('a[href="#cfdev-tab-options"] .cfdev-tab-count')
        .invoke('text')
        .then((text) => {
          expect(parseInt(text)).to.eq(count)
        })
    })
  })
})