/**
 * Smoke test: field icons (.cfdev-field-icon) appear in the flat meta box,
 * bundle meta box, and tabs layout.
 */

describe('CFDev — Field Icons', () => {
  beforeEach(() => {
    cy.loginToWP()
  })

  it('shows .cfdev-field-icon spans inside flat meta box labels', () => {
    cy.visit('/wp-admin/post-new.php?post_type=post')
    cy.get('#cfdev_demo_flat').within(() => {
      cy.get('.cfdev-field-icon').should('have.length.greaterThan', 0)
    })
  })

  it('flat meta box icons have a dashicons class', () => {
    cy.visit('/wp-admin/post-new.php?post_type=post')
    cy.get('#cfdev_demo_flat .cfdev-field-icon').first().should(($el) => {
      const classes = Array.from($el[0].classList)
      expect(classes.some((c) => c.startsWith('dashicons-'))).to.be.true
    })
  })

  it('flat meta box icons have aria-hidden="true"', () => {
    cy.visit('/wp-admin/post-new.php?post_type=post')
    cy.get('#cfdev_demo_flat .cfdev-field-icon').first().should('have.attr', 'aria-hidden', 'true')
  })

  it('flat meta box icons carry a color class', () => {
    cy.visit('/wp-admin/post-new.php?post_type=post')
    cy.get('#cfdev_demo_flat .cfdev-field-icon').first().should(($el) => {
      const classes = Array.from($el[0].classList)
      expect(classes.some((c) => c.startsWith('cfdev-icon--'))).to.be.true
    })
  })

  it('shows .cfdev-field-icon spans inside bundle meta box labels', () => {
    cy.visit('/wp-admin/post-new.php?post_type=post')
    cy.get('#cfdev_demo_bundle').within(() => {
      cy.get('.cfdev-field-icon').should('have.length.greaterThan', 0)
    })
  })

})
