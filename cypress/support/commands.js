/**
 * cy.loginToWP() — log in to WP admin and cache the session.
 * The session is reused across tests in the same spec file.
 *
 * Uses cy.request() instead of a browser form click: in CI (wp-env Docker),
 * clicking #wp-submit can hang on cold PHP-FPM startup, causing a 20 s timeout.
 * cy.request() goes through Cypress's HTTP layer which shares the browser cookie
 * jar — auth cookies set in the response are available to all subsequent cy.visit().
 * Omitting 'testcookie' in the body skips WordPress's cookie-presence guard;
 * wp_signon() still validates credentials against the database normally.
 */
Cypress.Commands.add('loginToWP', () => {
  cy.session(
    Cypress.env('WP_USER'),
    () => {
      cy.request({
        method: 'POST',
        url: '/wp-login.php',
        form: true,
        body: {
          log: Cypress.env('WP_USER'),
          pwd: Cypress.env('WP_PASS'),
          'wp-submit': 'Log In',
          redirect_to: '/wp-admin/',
        },
      })
      cy.visit('/wp-admin/')
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
  // WordPress redirects to post-new.php?wp-post-new-reload=true when auto_draft=1
  // is still set at publish time (no autosave has fired yet to reset it).
  // Force it to 0 so WordPress takes the normal save+redirect path.
  cy.window().then(win => {
    const input = win.document.getElementById('auto_draft')
    if (input) input.value = '0'
  })
  cy.get('#publish').click()
  // Wait for WP to save + redirect: URL changes from post-new.php → post.php?post=ID
  // URL-based check is more reliable than DOM notice (WP version-independent)
  // 45 s to handle slow CI runners under load (spec 09+ runs late in the suite)
  cy.url({ timeout: 45000 }).should('match', /[?&]post=\d+/)
})

/**
 * cy.setPostTitle(title) — type a title in the Classic Editor title field.
 */
Cypress.Commands.add('setPostTitle', (title) => {
  cy.get('#title').clear().type(title)
  // No blur: blur triggers an immediate autosave AJAX which can still be
  // in-flight when #publish is clicked, causing WP to submit auto_draft=1
  // and redirect to post-new.php?wp-post-new-reload=true instead of publishing.
  // The slug is generated server-side on save — no blur needed for correctness.
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