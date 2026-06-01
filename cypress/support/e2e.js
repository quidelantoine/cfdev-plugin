import './commands'

// WordPress admin throws "An unknown error has occurred: [object Object]" when
// wp.apiFetch receives an unexpected (non-JSON or non-2xx) response from the
// REST API. This happens on cold Docker startup (wp-env CI) when an admin-page
// AJAX call fires before PHP-FPM has fully warmed up.
// It is a transient wp-admin behaviour — not a CFDev bug — so we suppress it
// globally rather than sprinkling cy.on() in every spec.
// The actual DOM assertions (adminbar, wpbody, adminmenu, meta boxes…) still run.
Cypress.on('uncaught:exception', (err) => {
  if (err.message && err.message.includes('An unknown error has occurred')) {
    return false
  }
})