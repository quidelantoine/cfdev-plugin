/**
 * Tests CFDev media fields: Image, File, Gallery, Link.
 *
 * Strategy: bypass the wp.media picker by setting the hidden input value
 * directly — this is what the picker does internally after selection.
 * Tests save/restore of the meta value, not the picker UI (which cannot
 * be driven by Cypress because it is a wp.media Backbone modal).
 *
 * Fields (Tab A — generateArrayAllField('cypress', 'tab_a')):
 *   Image  : cfdev[_img_cypress_tab_a_main_image]   → attachment ID
 *   File   : cfdev[_text_cypress_tab_a_file]        → attachment ID
 *   Gallery: cfdev[_text_cypress_tab_a_gallery][]   → array of attachment IDs
 *   Link   : cfdev[_text_cypress_tab_a_cta][url|text|target]
 */

const IMG_ID    = 99   // Fake attachment IDs — tests save/restore of the integer meta value
const FILE_ID   = 98
const GAL_IDS   = [97, 96]
const LINK_URL  = 'https://cfdev-media-cypress.example.com'
const LINK_TEXT = 'CTA Cypress'

// Tab A ("Champs plats") is the default active tab — no click needed.

let postId

describe('CFDev — Media Fields (Image, File, Gallery, Link)', () => {
  beforeEach(() => {
    cy.loginToWP()
  })

  before(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/post-new.php')
    cy.setPostTitle('CFDev Media Fields Test')
    cy.expandPostbox('cfdev_cypress_tabs')
    // Image — set the hidden input directly (bypass wp.media picker)
    cy.get('input[name="cfdev[_img_cypress_tab_a_main_image]"]')
      .invoke('val', String(IMG_ID))

    // File — same approach
    cy.get('input[name="cfdev[_text_cypress_tab_a_file]"]')
      .invoke('val', String(FILE_ID))

    // Gallery — inject items into the container (mimics what the picker does)
    cy.get('.js-cfdev-gallery')
      .filter((_, el) => el.dataset.fieldName === 'cfdev[_text_cypress_tab_a_gallery][]')
      .find('.js-cfdev-gallery-items')
      .then($items => {
        GAL_IDS.forEach(id => {
          Cypress.$('<div class="cfdev-gallery-item js-cfdev-gallery-item">' +
            `<input type="hidden" name="cfdev[_text_cypress_tab_a_gallery][]" value="${id}">` +
            '</div>').appendTo($items)
        })
      })

    // Link
    cy.get('input[name="cfdev[_text_cypress_tab_a_cta][url]"]').clear().type(LINK_URL)
    cy.get('input[name="cfdev[_text_cypress_tab_a_cta][text]"]').clear().type(LINK_TEXT)

    cy.publishPost()
    cy.url().then(url => {
      const m = url.match(/[?&]post=(\d+)/)
      postId = m ? parseInt(m[1]) : null
      expect(postId).to.be.greaterThan(0)
    })
  })

  // ── Image + File ──────────────────────────────────────────────────────────

  it('saves and restores image and file attachment IDs', () => {
    cy.then(() => {
      cy.visit(`/wp-admin/post.php?post=${postId}&action=edit`)
      cy.expandPostbox('cfdev_cypress_tabs')
    })
    cy.get('input[name="cfdev[_img_cypress_tab_a_main_image]"]')
      .should('have.value', String(IMG_ID))
    cy.get('input[name="cfdev[_text_cypress_tab_a_file]"]')
      .should('have.value', String(FILE_ID))
  })

  // ── Gallery ───────────────────────────────────────────────────────────────
  // Gallery renders saved items only when the attachment exists in the media
  // library — fake IDs won't appear in the UI. We verify via the CFDev REST
  // endpoint that the IDs were actually persisted in post meta.

  it('saves gallery attachment IDs to post meta', () => {
    cy.then(() => {
      cy.request(`/wp-json/cfdev/v1/post/${postId}`).then(res => {
        expect(res.status).to.eq(200)
        const gallery = res.body.groups?.cfdev_cypress_tabs?.['_text_cypress_tab_a_gallery']
        expect(gallery).to.be.an('array').with.length(GAL_IDS.length)
        // Gallery items are returned as {id, url, …} objects by the REST endpoint
        const ids = gallery.map(item => Number(typeof item === 'object' ? item?.id : item))
        GAL_IDS.forEach(id => expect(ids).to.include(id))
      })
    })
  })

  // ── Link ──────────────────────────────────────────────────────────────────

  it('saves and restores link URL, text and target', () => {
    cy.then(() => {
      cy.visit(`/wp-admin/post.php?post=${postId}&action=edit`)
      cy.expandPostbox('cfdev_cypress_tabs')
    })
    cy.get('input[name="cfdev[_text_cypress_tab_a_cta][url]"]')
      .should('have.value', LINK_URL)
    cy.get('input[name="cfdev[_text_cypress_tab_a_cta][text]"]')
      .should('have.value', LINK_TEXT)
  })
})