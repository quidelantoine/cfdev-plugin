/**
 * Tests CFDev field types skipped in existing specs:
 *   - Datetime, Time        (date-picker variants, stored as Unix timestamp)
 *   - MultiSelect           (<select multiple>)
 *   - PostSelect / PostCheckboxes
 *   - TermSelect / TermCheckboxes
 *   - UserSelect / UserCheckboxes
 *
 * All fields from Tab A — generateArrayAllField('cypress', 'tab_a').
 *
 * Fixture strategy:
 *   - A "relation target" post is created in before() → PostSelect / PostCheckboxes.
 *   - First existing category (Uncategorized, always present) → TermSelect / TermCheckboxes.
 *   - First user in the UserSelect <select> (admin) → UserSelect / UserCheckboxes.
 */

const DATETIME_VAL = '06/02/2026 14:30'
const TIME_VAL     = '14:30'
const MULTI_VALS   = ['v1', 'v2']

// Tab A ("Champs plats") is the default active tab — no click needed.

let postId
let targetPostId
let categoryId
let adminUserId

describe('CFDev — Datetime / MultiSelect / Relations', () => {
  beforeEach(() => {
    cy.loginToWP()
  })

  before(() => {
    cy.loginToWP()

    // Resolve category ID from the WP REST API (public endpoint, no nonce needed)
    cy.request('/wp-json/wp/v2/categories?per_page=1&orderby=id&order=asc')
      .then(res => { categoryId = res.body[0]?.id })

    // Create the relation target post — needed for PostSelect / PostCheckboxes
    cy.visit('/wp-admin/post-new.php')
    cy.get('#title').clear().type('CFDev Relation Target').blur()
    cy.publishPost()
    cy.url().then(url => {
      const m = url.match(/[?&]post=(\d+)/)
      targetPostId = m ? parseInt(m[1]) : null
      expect(targetPostId).to.be.greaterThan(0)
    })

    // Create the main test post with all skipped fields filled
    cy.then(() => {
      cy.visit('/wp-admin/post-new.php')
      cy.setPostTitle('CFDev Skipped Fields Test')
      cy.expandPostbox('cfdev_cypress_tabs')

      // ── Datetime ─────────────────────────────────────────────────────────
      cy.get('input[name="cfdev[_date_cypress_tab_a_datetime]"]')
        .invoke('val', DATETIME_VAL).trigger('change', { force: true })

      // ── Time ─────────────────────────────────────────────────────────────
      cy.get('input[name="cfdev[_date_cypress_tab_a_time]"]')
        .invoke('val', TIME_VAL).trigger('change', { force: true })

      // ── MultiSelect ───────────────────────────────────────────────────────
      cy.get('select[name="cfdev[_text_cypress_tab_a_multiselect][]"]')
        .select(MULTI_VALS)

      // ── PostSelect / PostCheckboxes ───────────────────────────────────────
      cy.get('select[name="cfdev[_post_cypress_tab_a_selectpost]"]').then($sel => {
        if ($sel.find(`option[value="${targetPostId}"]`).length) {
          cy.wrap($sel).select(String(targetPostId))
        }
      })
      cy.get(`input[name="cfdev[_post_cypress_tab_a_checkboxespost][]"][value="${targetPostId}"]`)
        .check()

      // ── TermSelect / TermCheckboxes ───────────────────────────────────────
      cy.get('select[name="cfdev[_term_cypress_tab_a_selectterm]"]').then($sel => {
        if ($sel.find(`option[value="${categoryId}"]`).length) {
          cy.wrap($sel).select(String(categoryId))
        }
      })
      cy.get(`input[name="cfdev[_term_cypress_tab_a_checkboxesterm][]"][value="${categoryId}"]`)
        .check()

      // ── UserSelect / UserCheckboxes ───────────────────────────────────────
      // Resolve admin user ID from the first option in the select (avoids REST nonce)
      // The "none" option has value="-1", not "" — filter for real user IDs (> 0)
      cy.get('select[name="cfdev[_user_cypress_tab_a_selectuser]"] option')
        .filter((_, el) => parseInt(el.value) > 0)
        .first().then($opt => {
          adminUserId = parseInt($opt.val())
          cy.get('select[name="cfdev[_user_cypress_tab_a_selectuser]"]')
            .select($opt.val())
        })
      // Skip the hidden -1 placeholder; target the first real user checkbox
      cy.get('input[name="cfdev[_user_cypress_tab_a_reviewers][]"]')
        .filter((_, el) => parseInt(el.value) > 0)
        .first().then($cb => {
          adminUserId = parseInt($cb.val())
          cy.wrap($cb).check()
        })

      cy.publishPost()
      cy.url().then(url => {
        const m = url.match(/[?&]post=(\d+)/)
        postId = m ? parseInt(m[1]) : null
        expect(postId).to.be.greaterThan(0)
      })
    })
  })

  // ── Datetime + Time ───────────────────────────────────────────────────────

  it('saves and restores datetime and time fields', () => {
    cy.then(() => {
      cy.visit(`/wp-admin/post.php?post=${postId}&action=edit`)
      cy.expandPostbox('cfdev_cypress_tabs')
    })
    cy.get('input[name="cfdev[_date_cypress_tab_a_datetime]"]')
      .should('have.value', DATETIME_VAL)
    cy.get('input[name="cfdev[_date_cypress_tab_a_time]"]')
      .should('have.value', TIME_VAL)
  })

  // ── MultiSelect ───────────────────────────────────────────────────────────

  it('saves and restores multi_select values', () => {
    cy.then(() => {
      cy.visit(`/wp-admin/post.php?post=${postId}&action=edit`)
      cy.expandPostbox('cfdev_cypress_tabs')
    })
    cy.get('select[name="cfdev[_text_cypress_tab_a_multiselect][]"]').invoke('val')
      .should('deep.equal', MULTI_VALS)
  })

  // ── Post relations ────────────────────────────────────────────────────────

  it('saves and restores post_select and post_checkboxes', () => {
    cy.then(() => {
      cy.visit(`/wp-admin/post.php?post=${postId}&action=edit`)
      cy.expandPostbox('cfdev_cypress_tabs')
    })
    cy.get('select[name="cfdev[_post_cypress_tab_a_selectpost]"]')
      .should('have.value', String(targetPostId))
    cy.get(`input[name="cfdev[_post_cypress_tab_a_checkboxespost][]"][value="${targetPostId}"]`)
      .should('be.checked')
  })

  // ── Term relations ────────────────────────────────────────────────────────

  it('saves and restores term_select and term_checkboxes', () => {
    cy.then(() => {
      cy.visit(`/wp-admin/post.php?post=${postId}&action=edit`)
      cy.expandPostbox('cfdev_cypress_tabs')
    })
    cy.get('select[name="cfdev[_term_cypress_tab_a_selectterm]"]')
      .should('have.value', String(categoryId))
    cy.get(`input[name="cfdev[_term_cypress_tab_a_checkboxesterm][]"][value="${categoryId}"]`)
      .should('be.checked')
  })

  // ── User relations ────────────────────────────────────────────────────────

  it('saves and restores user_select and user_checkboxes', () => {
    cy.then(() => {
      cy.visit(`/wp-admin/post.php?post=${postId}&action=edit`)
      cy.expandPostbox('cfdev_cypress_tabs')
    })
    cy.then(() => {
      cy.get('select[name="cfdev[_user_cypress_tab_a_selectuser]"]')
        .should('have.value', String(adminUserId))
      cy.get(`input[name="cfdev[_user_cypress_tab_a_reviewers][]"][value="${adminUserId}"]`)
        .should('be.checked')
    })
  })
})