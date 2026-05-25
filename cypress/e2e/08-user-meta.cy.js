/**
 * Tests CFDev User Meta on the admin user profile (/wp-admin/profile.php).
 *
 * Three UserMeta instances registered in demo-user.php:
 *   1. Flat:   'cfdev_demo_user'       — generateArrayAllField('demo', 'user')
 *   2. Tabs:   'cfdev_demo_user_tabs'  — { 'Infos': generateArrayAllField('demo', 'user_tab_a'), 'Médias': [...] }
 *   3. Bundle: 'cfdev_demo_user_bundle'— bundle ID = _cfdev_demo_user_bundle
 *
 * Profile is persistent — values from previous runs remain. Tests overwrite specific
 * fields and verify the overwritten values. Bundle cleanup removes extra rows from
 * the END (using .last()) so row 0 keeps its name-attribute index.
 *
 * Skipped: image, file, wysiwyg, gallery, link, post/term/user relation fields.
 */

// Flat fields (demo, user)
const F = {
  text:       'cfdev[_text_demo_user_text]',
  textarea:   'cfdev[_text_demo_user_textarea]',
  qty:        'cfdev[_text_demo_user_qty]',
  range:      'cfdev[_text_demo_user_range]',
  email:      'cfdev[_text_demo_user_email]',
  url:        'cfdev[_text_demo_user_website]',
  tel:        'cfdev[_text_demo_user_phone]',
  toggle:     'cfdev[_text_demo_user_toggle]',
  checkbox:   'cfdev[_text_demo_user_checkbox]',
  checkboxes: 'cfdev[_text_demo_user_checkboxes][]',
  radios:     'cfdev[_text_demo_user_radios][]',
  yesno:      'cfdev[_text_demo_user_yesno]',
  select:     'cfdev[_text_demo_user_select]',
  color:      'cfdev[_text_demo_user_color]',
  date:       'cfdev[_date_demo_user_date]',
}

// Tabs — Infos tab fields (demo, user_tab_a)
const TA = {
  text:       'cfdev[_text_demo_user_tab_a_text]',
  textarea:   'cfdev[_text_demo_user_tab_a_textarea]',
  qty:        'cfdev[_text_demo_user_tab_a_qty]',
  range:      'cfdev[_text_demo_user_tab_a_range]',
  email:      'cfdev[_text_demo_user_tab_a_email]',
  url:        'cfdev[_text_demo_user_tab_a_website]',
  tel:        'cfdev[_text_demo_user_tab_a_phone]',
  toggle:     'cfdev[_text_demo_user_tab_a_toggle]',
  checkbox:   'cfdev[_text_demo_user_tab_a_checkbox]',
  checkboxes: 'cfdev[_text_demo_user_tab_a_checkboxes][]',
  radios:     'cfdev[_text_demo_user_tab_a_radios][]',
  yesno:      'cfdev[_text_demo_user_tab_a_yesno]',
  select:     'cfdev[_text_demo_user_tab_a_select]',
  color:      'cfdev[_text_demo_user_tab_a_color]',
  date:       'cfdev[_date_demo_user_tab_a_date]',
}

// Bundle (demo, user_bundle) — bundle ID = _cfdev_demo_user_bundle
const USER_BUNDLE = '_cfdev_demo_user_bundle'
const UB = {
  text:   '_text_demo_user_bundle_text',
  select: '_text_demo_user_bundle_select',
}
const nb = (row, fieldId) => `cfdev[${USER_BUNDLE}][${row}][${fieldId}]`

const saveProfile = () => {
  cy.get('#submit').click()
  cy.get('.notice-success, .updated, #message').should('exist')
}

describe('CFDev — User Meta (Profile)', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/profile.php')
  })

  it('shows all three user meta sections', () => {
    cy.contains('[DEMO] Profil').should('exist')
    cy.contains('[DEMO] Profil — Tabs').should('exist')
    cy.contains('[DEMO] Profil — Bundle').should('exist')
  })

  it('saves and restores all flat fields', () => {
    cy.get(`input[name="${F.text}"]`).clear().type('UserText')
    cy.get(`textarea[name="${F.textarea}"]`).clear().type('User textarea')
    cy.get(`input[name="${F.qty}"]`).clear().type('25')
    cy.get(`input[name="${F.range}"]`).invoke('val', '20').trigger('change')
    cy.get(`input[name="${F.email}"]`).clear().type('user@cypress.io')
    cy.get(`input[name="${F.url}"]`).clear().type('https://user.cypress.io')
    cy.get(`input[name="${F.tel}"]`).clear().type('+33 7 11 22 33 44')
    cy.get(`input[name="${F.color}"]`).invoke('val', '#aaccee').trigger('change', { force: true })
    cy.get(`input[name="${F.date}"]`).invoke('val', '01/15/2027').trigger('change', { force: true })
    cy.get(`input[name="${F.toggle}"]`).check()
    cy.get(`input[name="${F.checkbox}"]`).check()
    cy.get(`input[name="${F.yesno}"][value="yes"]`).check()
    cy.get(`input[name="${F.radios}"][value="v2"]`).check()
    cy.get(`select[name="${F.select}"]`).select('v3')
    cy.get(`input[name="${F.checkboxes}"][value="v1"]`).check()
    cy.get(`input[name="${F.checkboxes}"][value="v2"]`).check()

    saveProfile()

    cy.get(`input[name="${F.text}"]`).should('have.value', 'UserText')
    cy.get(`textarea[name="${F.textarea}"]`).should('have.value', 'User textarea')
    cy.get(`input[name="${F.qty}"]`).should('have.value', '25')
    cy.get(`input[name="${F.range}"]`).should('have.value', '20')
    cy.get(`input[name="${F.email}"]`).should('have.value', 'user@cypress.io')
    cy.get(`input[name="${F.url}"]`).should('have.value', 'https://user.cypress.io')
    cy.get(`input[name="${F.tel}"]`).should('have.value', '+33 7 11 22 33 44')
    cy.get(`input[name="${F.color}"]`).should('have.value', '#aaccee')
    cy.get(`input[name="${F.date}"]`).should('have.value', '01/15/2027')
    cy.get(`input[name="${F.toggle}"]`).should('be.checked')
    cy.get(`input[name="${F.checkbox}"]`).should('be.checked')
    cy.get(`input[name="${F.yesno}"][value="yes"]`).should('be.checked')
    cy.get(`input[name="${F.radios}"][value="v2"]`).should('be.checked')
    cy.get(`input[name="${F.radios}"][value="v1"]`).should('not.be.checked')
    cy.get(`select[name="${F.select}"]`).should('have.value', 'v3')
    cy.get(`input[name="${F.checkboxes}"][value="v1"]`).should('be.checked')
    cy.get(`input[name="${F.checkboxes}"][value="v2"]`).should('be.checked')
    cy.get(`input[name="${F.checkboxes}"][value="v3"]`).should('not.be.checked')
  })

  it('saves and restores all Tabs Infos fields', () => {
    cy.contains('[DEMO] Profil — Tabs').should('exist')
    cy.get('.cfdev-tabs ul').should('contain', 'Infos')
    cy.get('.cfdev-tabs ul').should('contain', 'Médias')

    // Infos is the active tab by default
    cy.get(`input[name="${TA.text}"]`).clear().type('UserTabText')
    cy.get(`textarea[name="${TA.textarea}"]`).clear().type('User tab textarea')
    cy.get(`input[name="${TA.qty}"]`).clear().type('77')
    cy.get(`input[name="${TA.range}"]`).invoke('val', '50').trigger('change')
    cy.get(`input[name="${TA.email}"]`).clear().type('usertab@cypress.io')
    cy.get(`input[name="${TA.url}"]`).clear().type('https://usertab.cypress.io')
    cy.get(`input[name="${TA.tel}"]`).clear().type('+33 8 11 22 33 44')
    cy.get(`input[name="${TA.color}"]`).invoke('val', '#99aabb').trigger('change', { force: true })
    cy.get(`input[name="${TA.date}"]`).invoke('val', '05/20/2027').trigger('change', { force: true })
    cy.get(`input[name="${TA.toggle}"]`).check()
    cy.get(`input[name="${TA.checkbox}"]`).check()
    cy.get(`input[name="${TA.yesno}"][value="yes"]`).check()
    cy.get(`input[name="${TA.radios}"][value="v1"]`).check()
    cy.get(`select[name="${TA.select}"]`).select('v1')
    cy.get(`input[name="${TA.checkboxes}"][value="v2"]`).check()
    cy.get(`input[name="${TA.checkboxes}"][value="v3"]`).check()

    saveProfile()

    cy.get(`input[name="${TA.text}"]`).should('have.value', 'UserTabText')
    cy.get(`textarea[name="${TA.textarea}"]`).should('have.value', 'User tab textarea')
    cy.get(`input[name="${TA.qty}"]`).should('have.value', '77')
    cy.get(`input[name="${TA.range}"]`).should('have.value', '50')
    cy.get(`input[name="${TA.email}"]`).should('have.value', 'usertab@cypress.io')
    cy.get(`input[name="${TA.url}"]`).should('have.value', 'https://usertab.cypress.io')
    cy.get(`input[name="${TA.tel}"]`).should('have.value', '+33 8 11 22 33 44')
    cy.get(`input[name="${TA.color}"]`).should('have.value', '#99aabb')
    cy.get(`input[name="${TA.date}"]`).should('have.value', '05/20/2027')
    cy.get(`input[name="${TA.toggle}"]`).should('be.checked')
    cy.get(`input[name="${TA.checkbox}"]`).should('be.checked')
    cy.get(`input[name="${TA.yesno}"][value="yes"]`).should('be.checked')
    cy.get(`input[name="${TA.radios}"][value="v1"]`).should('be.checked')
    cy.get(`input[name="${TA.radios}"][value="v2"]`).should('not.be.checked')
    cy.get(`select[name="${TA.select}"]`).should('have.value', 'v1')
    cy.get(`input[name="${TA.checkboxes}"][value="v2"]`).should('be.checked')
    cy.get(`input[name="${TA.checkboxes}"][value="v3"]`).should('be.checked')
    cy.get(`input[name="${TA.checkboxes}"][value="v1"]`).should('not.be.checked')
  })

  it('saves bundle row 0 and adds a second row', () => {
    cy.get(`#${USER_BUNDLE} .js-cfdev-add-bundle`).should('exist')

    // Remove extra rows from the end so row 0 keeps its name-attribute index
    cy.get(`#${USER_BUNDLE} .js-cfdev-sortable-item`).then($rows => {
      const extraRows = $rows.length - 1
      for (let i = 0; i < extraRows; i++) {
        cy.get(`#${USER_BUNDLE} .js-cfdev-remove-sortable`).last().click()
      }
    })

    cy.get(`input[name="${nb(0, UB.text)}"]`).invoke('val', 'Bundle 1').trigger('input')
    cy.get(`select[name="${nb(0, UB.select)}"]`).select('v1')
    cy.get(`#${USER_BUNDLE} .js-cfdev-add-bundle`).click()
    cy.get(`input[name="${nb(1, UB.text)}"]`).invoke('val', 'Bundle 2').trigger('input')
    cy.get(`select[name="${nb(1, UB.select)}"]`).select('v2')

    saveProfile()

    cy.get(`#${USER_BUNDLE} .js-cfdev-sortable-item`).should('have.length', 2)
    cy.get(`input[name="${nb(0, UB.text)}"]`).should('have.value', 'Bundle 1')
    cy.get(`select[name="${nb(0, UB.select)}"]`).should('have.value', 'v1')
    cy.get(`input[name="${nb(1, UB.text)}"]`).should('have.value', 'Bundle 2')
    cy.get(`select[name="${nb(1, UB.select)}"]`).should('have.value', 'v2')
  })
})
