/**
 * Options pages — 16g : Code modal (snippet get_option, pas de CacheManager).
 *
 * Run: npx cypress run --spec "cypress/e2e/16g-options-code-modal.cy.js" --browser chrome
 */

describe('CFDev — Code modal pour les options', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/admin.php?page=cfdev')
    cy.get('a[href="#cfdev-tab-options"]').click()
    cy.get('#cfdev-tab-options').should('be.visible')
  })

  it('le bouton Code est présent sur les groupes option', () => {
    cy.get('#cfdev-tab-options .cfdev-group').first().within(() => {
      cy.get('.cfdev-btn-code').should('exist')
    })
  })

  it('le modal Code s\'ouvre sur un groupe option', () => {
    cy.get('#cfdev-tab-options')
      .contains('.cfdev-group-id', 'cfdev_options_demo')
      .closest('.cfdev-group')
      .find('.cfdev-btn-code')
      .click()

    cy.get('#cfdev-code-modal').should('be.visible')
    cy.get('#cfdev-code-group-id').should('contain', 'cfdev_options_demo')
  })

  it('le snippet Display contient get_option() et pas de CacheManager', () => {
    cy.get('#cfdev-tab-options')
      .contains('.cfdev-group-id', 'cfdev_options_demo')
      .closest('.cfdev-group')
      .find('.cfdev-btn-code')
      .click()

    cy.get('#cfdev-code-tab-display').should('have.class', 'is-active')

    cy.get('#cfdev-code-output').invoke('text').then((code) => {
      expect(code).to.include('get_option')
      expect(code).to.include('<?php')
      expect(code).not.to.include('CacheManager')
    })
  })

  it('le snippet Raw n\'a pas d\'echo ni de balises HTML', () => {
    cy.get('#cfdev-tab-options')
      .contains('.cfdev-group-id', 'cfdev_options_demo')
      .closest('.cfdev-group')
      .find('.cfdev-btn-code')
      .click()

    cy.get('#cfdev-code-tab-raw').click()
    cy.get('#cfdev-code-output').invoke('text').then((code) => {
      expect(code).not.to.include('echo ')
      expect(code).not.to.include('<a ')
      expect(code).not.to.include('<img')
    })
  })

  it('le snippet d\'un bundle option utilise Field::decodeMetaValue(get_option(...))', () => {
    cy.get('#cfdev-tab-options')
      .contains('.cfdev-group-id', 'cfdev_options_bundle')
      .closest('.cfdev-group')
      .find('.cfdev-btn-code')
      .click()

    cy.get('#cfdev-code-output').invoke('text').then((code) => {
      expect(code).to.include('get_option')
      expect(code).to.include('decodeMetaValue')
      expect(code).to.include('foreach')
    })
  })
})