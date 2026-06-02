/**
 * Options pages — 16h : REST API endpoint /cfdev/v1/options/{page_id}.
 *
 * cy.request() envoie le cookie de session mais pas le nonce WP REST.
 * On récupère window.wpApiSettings.nonce après chaque cy.visit() admin
 * et on l'injecte via X-WP-Nonce pour que WP authentifie la requête.
 *
 * Run: npx cypress run --spec "cypress/e2e/16h-options-rest.cy.js" --browser chrome
 */

const RGL_NAME  = 'cfdev[_opt_rgl_site_name]'
const RGL_TAGLINE = 'cfdev[_opt_rgl_tagline]'
const MAIN_TEXT = 'cfdev[_text_opt_main_text]'
const BUNDLE_ID = '_opt_bundle'
const BF_TEXT   = '_text_opt_bundle_text'
const nb        = (row, fieldId) => `cfdev[${BUNDLE_ID}][${row}][${fieldId}]`

describe('CFDev — REST API options', () => {
  let restNonce = ''

  const apiRequest = (urlOrOpts) => {
    const opts = typeof urlOrOpts === 'string' ? { url: urlOrOpts } : { ...urlOrOpts }
    opts.headers = { ...opts.headers, 'X-WP-Nonce': restNonce }
    return cy.request(opts)
  }

  before(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/options-general.php?page=cfdev-cfdev_options_reglages')
    cy.window().then(win => { restNonce = win.wpApiSettings?.nonce ?? '' })
    cy.get(`input[name="${RGL_NAME}"]`).clear().type('Agence REST Cypress')
    cy.get(`input[name="${RGL_TAGLINE}"]`).clear().type('Slogan REST')
    cy.get('#submit').click()
    cy.get('.notice-success', { timeout: 15000 }).should('exist')
  })

  it('GET /cfdev/v1/options/cfdev_options_reglages retourne 200 avec les valeurs', () => {
    apiRequest('/wp-json/cfdev/v1/options/cfdev_options_reglages').then((res) => {
      expect(res.status).to.eq(200)
      expect(res.body).to.have.property('page', 'cfdev_options_reglages')
      expect(res.body).to.have.property('groups')

      const group = res.body.groups?.cfdev_options_reglages
      expect(group).to.be.an('object')
      expect(group).to.have.property('_opt_rgl_site_name', 'Agence REST Cypress')
      expect(group).to.have.property('_opt_rgl_tagline', 'Slogan REST')
    })
  })

  it('GET /cfdev/v1/options/cfdev_options_reglages ne contient que les champs rest:true', () => {
    cy.loginToWP()
    apiRequest('/wp-json/cfdev/v1/options/cfdev_options_reglages').then((res) => {
      const group = res.body.groups?.cfdev_options_reglages ?? {}
      expect(group).not.to.have.property('_opt_rgl_address')
      expect(group).not.to.have.property('_opt_rgl_logo')
    })
  })

  it('GET /cfdev/v1/options/inexistant retourne 404', () => {
    cy.loginToWP()
    apiRequest({ url: '/wp-json/cfdev/v1/options/inexistant_xyz', failOnStatusCode: false })
      .then((res) => {
        expect(res.status).to.eq(404)
      })
  })

  it('GET /cfdev/v1/options/cfdev_options_demo retourne les champs plats avec valeurs', () => {
    cy.loginToWP()
    cy.visit('/wp-admin/admin.php?page=cfdev-cfdev_options_demo')
    cy.window().then(win => { restNonce = win.wpApiSettings?.nonce ?? '' })
    cy.get(`input[name="${MAIN_TEXT}"]`).clear().type('REST_Demo_Text')
    cy.get('#submit').click()
    cy.get('.notice-success', { timeout: 15000 }).should('exist')

    apiRequest('/wp-json/cfdev/v1/options/cfdev_options_demo').then((res) => {
      expect(res.status).to.eq(200)
      const group = res.body.groups?.cfdev_options_demo ?? {}
      expect(group).to.have.property('_text_opt_main_text', 'REST_Demo_Text')
    })
  })

  it('GET /cfdev/v1/options/cfdev_options_bundle retourne les lignes du bundle décodées', () => {
    cy.loginToWP()
    cy.visit('/wp-admin/admin.php?page=cfdev-cfdev_options_bundle')
    cy.window().then(win => { restNonce = win.wpApiSettings?.nonce ?? '' })
    cy.get(`input[name="${nb(0, BF_TEXT)}"]`).clear().type('BundleREST')
    cy.get('#submit').click()
    cy.get('.notice-success', { timeout: 15000 }).should('exist')

    apiRequest('/wp-json/cfdev/v1/options/cfdev_options_bundle').then((res) => {
      expect(res.status).to.eq(200)
      const group  = res.body.groups?.cfdev_options_bundle ?? {}
      const bundle = group[BUNDLE_ID]
      expect(bundle).to.be.an('array').with.length.gte(1)
      expect(bundle[0]).to.have.property(BF_TEXT, 'BundleREST')
    })
  })
})