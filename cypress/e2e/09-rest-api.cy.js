/**
 * Tests CFDev REST API endpoints via cy.request().
 *
 * Routes:
 *   GET /wp-json/cfdev/v1/post/{id}          — public for published posts
 *   GET /wp-json/cfdev/v1/term/category/{id} — public for public taxonomies
 *   GET /wp-json/cfdev/v1/user/{id}          — requires auth (401 without nonce/cookie)
 *
 * REST-exposed fields:
 *   Post:  cfdev_demo_bundle / _cfdev_demo_bundle (bundle)
 *   Term:  category / _category (Galerie bundle in accordion)
 *   User:  none declared → 404 after auth (but 401 reached first without nonce)
 *
 * A before() hook creates one post and one category with known data;
 * all tests then use cy.request() without any browser navigation.
 */

// Post bundle (demo, bundle) — meta box ID: cfdev_demo_bundle
const POST_GROUP  = 'cfdev_demo_bundle'
const POST_BUNDLE = '_cfdev_demo_bundle'
const BF = {
  text:   '_text_demo_bundle_text',
  select: '_text_demo_bundle_select',
}
const nb = (row, fieldId) => `cfdev[${POST_BUNDLE}][${row}][${fieldId}]`

// Term accordion Galerie bundle (demo, term_acc_bundle) — bundle ID: _category
const TERM_GROUP  = 'category'
const TERM_BUNDLE = '_category'
const ACCB = {
  text:   '_text_demo_term_acc_bundle_text',
  select: '_text_demo_term_acc_bundle_select',
}
const nt = (row, fieldId) => `cfdev[${TERM_BUNDLE}][${row}][${fieldId}]`

const POST_TEXT   = 'RestBundleText'
const POST_SELECT = 'v1'
const TERM_TEXT   = 'RestTermText'
const TERM_SELECT = 'v2'

let postId
let termId

describe('CFDev — REST API', () => {
  before(() => {
    cy.loginToWP()

    // ── Create post with bundle row 0 ────────────────────────────────────
    cy.visit('/wp-admin/post-new.php')
    cy.expandPostbox('cfdev_demo_bundle')
    cy.setPostTitle('Cypress REST Post')
    cy.get(`input[name="${nb(0, BF.text)}"]`).invoke('val', POST_TEXT).trigger('input')
    cy.get(`select[name="${nb(0, BF.select)}"]`).select(POST_SELECT)
    cy.publishPost()
    cy.url().then(url => {
      const m = url.match(/[?&]post=(\d+)/)
      postId = m ? parseInt(m[1]) : null
    })

    // ── Create category and fill Galerie bundle on the edit page ─────────
    const catName = 'Cypress REST Cat ' + Date.now()
    cy.visit('/wp-admin/edit-tags.php?taxonomy=category')
    cy.get('#tag-name').clear().type(catName)
    cy.get('#submit').click()
    cy.contains('#the-list a.row-title', catName, { timeout: 10000 })
      .invoke('attr', 'href')
      .then(href => cy.visit(href))
    cy.url().then(url => {
      const m = url.match(/tag_ID=(\d+)/)
      termId = m ? parseInt(m[1]) : null
    })
    // The Galerie section is collapsed — expand it
    cy.get('.js-cfdev-accordion').contains('h3', 'Galerie').click()
    cy.get(`input[name="${nt(0, ACCB.text)}"]`).invoke('val', TERM_TEXT).trigger('input')
    cy.get(`select[name="${nt(0, ACCB.select)}"]`).select(TERM_SELECT)
    cy.get('form#edittag [type="submit"]').first().click()
    cy.get('.notice-success, .updated, #message').should('exist')
  })

  // ── Post endpoint ──────────────────────────────────────────────────────

  it('GET /post/{id} returns 200 with correct bundle data and only rest-flagged groups', () => {
    cy.request(`/wp-json/cfdev/v1/post/${postId}`).then(res => {
      expect(res.status).to.eq(200)
      expect(res.body).to.have.property('id', postId)
      expect(res.body).to.have.property('groups')

      // Bundle group: rest:true on the bundle itself
      const bundle = res.body.groups?.[POST_GROUP]?.[POST_BUNDLE]
      expect(bundle).to.be.an('array').with.length.gte(1)
      expect(bundle[0]).to.have.property(BF.text, POST_TEXT)
      expect(bundle[0]).to.have.property(BF.select, POST_SELECT)

      // cfdev_demo_flat is present (all its fields have rest:true via generateArrayAllField)
      expect(res.body.groups).to.have.property('cfdev_demo_flat')

      // cfdev_demo_extra_rules has no rest:true fields — must not appear
      expect(res.body.groups).not.to.have.property('cfdev_demo_extra_rules')
    })
  })

  it('GET /post/99999999 returns 404', () => {
    cy.request({ url: '/wp-json/cfdev/v1/post/99999999', failOnStatusCode: false }).then(res => {
      expect(res.status).to.eq(404)
    })
  })

  // ── Term endpoint ──────────────────────────────────────────────────────

  it('GET /term/category/{id} returns 200 with Galerie bundle data and no flat term fields', () => {
    cy.request(`/wp-json/cfdev/v1/term/category/${termId}`).then(res => {
      expect(res.status).to.eq(200)
      expect(res.body).to.have.property('id', termId)
      expect(res.body).to.have.property('taxonomy', 'category')
      expect(res.body).to.have.property('groups')

      const bundle = res.body.groups?.[TERM_GROUP]?.[TERM_BUNDLE]
      expect(bundle).to.be.an('array').with.length.gte(1)
      expect(bundle[0]).to.have.property(ACCB.text, TERM_TEXT)
      expect(bundle[0]).to.have.property(ACCB.select, TERM_SELECT)

      // Flat term fields are not rest-flagged — must not appear at group level
      expect(res.body.groups?.[TERM_GROUP]).not.to.have.property('_text_demo_term_text')
    })
  })

  it('GET /term/category/99999999 returns 404', () => {
    cy.request({ url: '/wp-json/cfdev/v1/term/category/99999999', failOnStatusCode: false }).then(res => {
      expect(res.status).to.eq(404)
    })
  })

  // ── User endpoint ──────────────────────────────────────────────────────

  it('GET /user/1 returns 401 or 403 (auth required — no rest fields declared)', () => {
    // With session cookie but no X-WP-Nonce header, WP REST returns 403.
    // Without any cookie it returns 401. Either way the endpoint is protected.
    cy.request({ url: '/wp-json/cfdev/v1/user/1', failOnStatusCode: false }).then(res => {
      expect(res.status).to.be.oneOf([401, 403])
    })
  })
})
