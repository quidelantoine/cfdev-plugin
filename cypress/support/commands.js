/**
 * cy.loginToWP() — log in to WP admin and cache the session.
 * The session is reused across tests in the same spec file.
 */
Cypress.Commands.add('loginToWP', () => {
  cy.session(
    Cypress.env('WP_USER'),
    () => {
      cy.visit('/wp-login.php')
      cy.get('#user_login').type(Cypress.env('WP_USER'))
      cy.get('#user_pass').type(Cypress.env('WP_PASS'), { log: false })
      cy.get('#wp-submit').click()
      cy.url().should('include', '/wp-admin')
    },
    {
      cacheAcrossSpecs: true,
    }
  )
})

/**
 * cy.publishPost() — click Publish and wait for success.
 * Works with the Classic Editor plugin.
 */
Cypress.Commands.add('publishPost', () => {
  cy.get('#publish').click()
  // 35 s : publish + redirect + page render can be slow on CI under load
  cy.get('#message.notice-success, .notice-success', { timeout: 35000 }).should('exist')
})

/**
 * cy.setPostTitle(title) — type a title in the Classic Editor title field.
 */
Cypress.Commands.add('setPostTitle', (title) => {
  cy.get('#title').clear().type(title)
  // Dismiss autosave / click away so the slug is generated
  cy.get('#title').blur()
})

/**
 * cy.expandPostbox(id) — ensure a WP metabox is expanded.
 * WP saves collapsed state in user meta; this clicks the toggle if needed.
 */
Cypress.Commands.add('expandPostbox', (id) => {
  cy.get(`#${id}`).then(($box) => {
    if ($box.hasClass('closed')) {
      cy.wrap($box).find('.handlediv').click()
    }
  })
  cy.get(`#${id} .inside`).should('be.visible')
})