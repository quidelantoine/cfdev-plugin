describe('WordPress Admin — Login', () => {
  it('logs in with valid credentials and reaches wp-admin', () => {
    cy.visit('/wp-login.php')
    cy.get('#user_login').type(Cypress.env('WP_USER'))
    cy.get('#user_pass').type(Cypress.env('WP_PASS'), { log: false })
    cy.get('#wp-submit').click()

    cy.url().should('include', '/wp-admin')
    cy.get('#wpadminbar').should('exist')
    cy.get('#wpbody').should('exist')
    cy.get('#adminmenu').should('exist')
  })

  // Wrong-credentials test runs LAST to avoid WordPress throttling the next correct login.
  // cy.clearCookies() ensures wp-login.php renders the form directly (no redirect to wp-admin
  // when already logged in, which would trigger a heavy PHP load and risk an FPM crash).
  it('shows an error with wrong credentials', () => {
    cy.clearCookies()
    cy.visit('/wp-login.php')
    cy.get('#user_login').type('wrong_user')
    cy.get('#user_pass').type('wrong_pass', { log: false })
    cy.get('#wp-submit').click()

    cy.get('#login_error').should('exist')
    cy.url().should('include', '/wp-login.php')
  })
})
