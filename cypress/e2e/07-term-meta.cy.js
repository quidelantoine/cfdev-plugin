/**
 * Tests CFDev Term Meta on taxonomy 'category'.
 *
 * Four TermMeta instances registered in demo-term.php:
 *   1. Flat:             generateArrayAllField('demo', 'term')
 *   2. Bundle:           bundle ID = _category (TermMeta $this->id = 'category', buildId prepends _)
 *   3. Tabs:             Onglet A / Onglet B
 *   4. Accordion+Bundle: Infos section + Galerie bundle (bundle ID = _category, same as #2)
 *
 * Strategy: create a fresh category per test (timestamp suffix avoids duplicates),
 * then navigate to its edit page to fill + verify CFDev fields.
 *
 * Save form: WP 6.7+ uses term.php with form#edittag; submit via [type="submit"].first()
 *
 * Skipped: image, file, wysiwyg, gallery, link, post/term/user relation fields.
 */

// Flat (demo, term)
const F = {
  text:       'cfdev[_text_demo_term_text]',
  textarea:   'cfdev[_text_demo_term_textarea]',
  qty:        'cfdev[_text_demo_term_qty]',
  range:      'cfdev[_text_demo_term_range]',
  email:      'cfdev[_text_demo_term_email]',
  url:        'cfdev[_text_demo_term_website]',
  tel:        'cfdev[_text_demo_term_phone]',
  toggle:     'cfdev[_text_demo_term_toggle]',
  checkbox:   'cfdev[_text_demo_term_checkbox]',
  checkboxes: 'cfdev[_text_demo_term_checkboxes][]',
  radios:     'cfdev[_text_demo_term_radios][]',
  yesno:      'cfdev[_text_demo_term_yesno]',
  select:     'cfdev[_text_demo_term_select]',
  color:      'cfdev[_text_demo_term_color]',
  date:       'cfdev[_date_demo_term_date]',
}

// Term bundle (demo, term_bundle) — bundle ID = _category
const TERM_BUNDLE = '_category'
const BF = {
  text:       '_text_demo_term_bundle_text',
  toggle:     '_text_demo_term_bundle_toggle',
  yesno:      '_text_demo_term_bundle_yesno',
  radios:     '_text_demo_term_bundle_radios',
  checkboxes: '_text_demo_term_bundle_checkboxes',
  select:     '_text_demo_term_bundle_select',
}
const nb = (row, fieldId) => `cfdev[${TERM_BUNDLE}][${row}][${fieldId}]`

// Tabs — Onglet A fields (demo, term_tab_a)
const TA = {
  text:       'cfdev[_text_demo_term_tab_a_text]',
  textarea:   'cfdev[_text_demo_term_tab_a_textarea]',
  qty:        'cfdev[_text_demo_term_tab_a_qty]',
  range:      'cfdev[_text_demo_term_tab_a_range]',
  email:      'cfdev[_text_demo_term_tab_a_email]',
  url:        'cfdev[_text_demo_term_tab_a_website]',
  tel:        'cfdev[_text_demo_term_tab_a_phone]',
  toggle:     'cfdev[_text_demo_term_tab_a_toggle]',
  checkbox:   'cfdev[_text_demo_term_tab_a_checkbox]',
  checkboxes: 'cfdev[_text_demo_term_tab_a_checkboxes][]',
  radios:     'cfdev[_text_demo_term_tab_a_radios][]',
  yesno:      'cfdev[_text_demo_term_tab_a_yesno]',
  select:     'cfdev[_text_demo_term_tab_a_select]',
  color:      'cfdev[_text_demo_term_tab_a_color]',
  date:       'cfdev[_date_demo_term_tab_a_date]',
}

// Accordion — Infos fields (demo, term_acc_a)
const AI = {
  text:       'cfdev[_text_demo_term_acc_a_text]',
  textarea:   'cfdev[_text_demo_term_acc_a_textarea]',
  qty:        'cfdev[_text_demo_term_acc_a_qty]',
  range:      'cfdev[_text_demo_term_acc_a_range]',
  email:      'cfdev[_text_demo_term_acc_a_email]',
  url:        'cfdev[_text_demo_term_acc_a_website]',
  tel:        'cfdev[_text_demo_term_acc_a_phone]',
  toggle:     'cfdev[_text_demo_term_acc_a_toggle]',
  checkbox:   'cfdev[_text_demo_term_acc_a_checkbox]',
  checkboxes: 'cfdev[_text_demo_term_acc_a_checkboxes][]',
  radios:     'cfdev[_text_demo_term_acc_a_radios][]',
  yesno:      'cfdev[_text_demo_term_acc_a_yesno]',
  select:     'cfdev[_text_demo_term_acc_a_select]',
  color:      'cfdev[_text_demo_term_acc_a_color]',
  date:       'cfdev[_date_demo_term_acc_a_date]',
}

// Accordion Galerie bundle (demo, term_acc_bundle) — bundle ID = _category (same as BF)
const ACCB = {
  text:   '_text_demo_term_acc_bundle_text',
  select: '_text_demo_term_acc_bundle_select',
}

// Unique suffix per test run — prevents duplicate-name rejection by WordPress
const TS = Date.now()

const createAndEditCategory = (base) => {
  const name = `${base}-${TS}`
  cy.visit('/wp-admin/edit-tags.php?taxonomy=category')
  cy.get('#tag-name').clear().type(name)
  cy.get('#submit').click()
  cy.contains('#the-list a.row-title', name, { timeout: 10000 })
    .invoke('attr', 'href')
    .then(href => cy.visit(href))
}

const saveTermAndReload = () => {
  cy.get('form#edittag [type="submit"]').first().click()
  cy.get('.notice-success, .updated, #message').should('exist')
}

describe('CFDev — Term Meta (Category)', () => {
  beforeEach(() => {
    cy.loginToWP()
  })

  it('shows all four term meta sections on category edit', () => {
    createAndEditCategory('Cypress — Term Presence')
    cy.contains('Catégorie — Tous Les Champs').should('exist')
    cy.contains('Catégorie — Bundle').should('exist')
    cy.contains('Catégorie — Tabs').should('exist')
    cy.contains('Catégorie — Accordéon With Bundle').should('exist')
  })

  it('saves and restores all flat fields', () => {
    createAndEditCategory('Cypress — Term Flat')

    cy.get(`input[name="${F.text}"]`).clear().type('TermText')
    cy.get(`textarea[name="${F.textarea}"]`).clear().type('Term textarea')
    cy.get(`input[name="${F.qty}"]`).clear().type('12')
    cy.get(`input[name="${F.range}"]`).invoke('val', '50').trigger('change')
    cy.get(`input[name="${F.email}"]`).clear().type('term@cypress.io')
    cy.get(`input[name="${F.url}"]`).clear().type('https://term.cypress.io')
    cy.get(`input[name="${F.tel}"]`).clear().type('+33 2 11 22 33 44')
    cy.get(`input[name="${F.color}"]`).invoke('val', '#112233').trigger('change', { force: true })
    cy.get(`input[name="${F.date}"]`).invoke('val', '04/01/2026').trigger('change', { force: true })
    cy.get(`input[name="${F.toggle}"]`).check()
    cy.get(`input[name="${F.checkbox}"]`).check()
    cy.get(`input[name="${F.yesno}"][value="yes"]`).check()
    cy.get(`input[name="${F.radios}"][value="v1"]`).check()
    cy.get(`select[name="${F.select}"]`).select('v2')
    cy.get(`input[name="${F.checkboxes}"][value="v1"]`).check()
    cy.get(`input[name="${F.checkboxes}"][value="v3"]`).check()

    saveTermAndReload()

    cy.get(`input[name="${F.text}"]`).should('have.value', 'TermText')
    cy.get(`textarea[name="${F.textarea}"]`).should('have.value', 'Term textarea')
    cy.get(`input[name="${F.qty}"]`).should('have.value', '12')
    cy.get(`input[name="${F.range}"]`).should('have.value', '50')
    cy.get(`input[name="${F.email}"]`).should('have.value', 'term@cypress.io')
    cy.get(`input[name="${F.url}"]`).should('have.value', 'https://term.cypress.io')
    cy.get(`input[name="${F.tel}"]`).should('have.value', '+33 2 11 22 33 44')
    cy.get(`input[name="${F.color}"]`).should('have.value', '#112233')
    cy.get(`input[name="${F.date}"]`).should('have.value', '04/01/2026')
    cy.get(`input[name="${F.toggle}"]`).should('be.checked')
    cy.get(`input[name="${F.checkbox}"]`).should('be.checked')
    cy.get(`input[name="${F.yesno}"][value="yes"]`).should('be.checked')
    cy.get(`input[name="${F.radios}"][value="v1"]`).should('be.checked')
    cy.get(`input[name="${F.radios}"][value="v2"]`).should('not.be.checked')
    cy.get(`select[name="${F.select}"]`).should('have.value', 'v2')
    cy.get(`input[name="${F.checkboxes}"][value="v1"]`).should('be.checked')
    cy.get(`input[name="${F.checkboxes}"][value="v3"]`).should('be.checked')
    cy.get(`input[name="${F.checkboxes}"][value="v2"]`).should('not.be.checked')
  })

  it('saves bundle row 0 fields and adds a second row', () => {
    createAndEditCategory('Cypress — Term Bundle')

    // Fill row 0
    cy.get(`input[name="${nb(0, BF.text)}"]`).clear().type('Ligne 1')
    cy.get(`input[name="${nb(0, BF.toggle)}"]`).check()
    cy.get(`input[name="${nb(0, BF.yesno)}"][value="yes"]`).check()
    cy.get(`input[name="${nb(0, BF.radios)}[]"][value="v2"]`).check()
    cy.get(`input[name="${nb(0, BF.checkboxes)}[]"][value="v1"]`).check()
    cy.get(`input[name="${nb(0, BF.checkboxes)}[]"][value="v3"]`).check()
    cy.get(`select[name="${nb(0, BF.select)}"]`).select('v3')

    // Add row 1 — scope to this postbox to avoid the accordion bundle's add button
    cy.contains('h2.cfdev-postbox-title', 'Catégorie — Bundle')
      .closest('.cfdev-postbox').find('.js-cfdev-add-bundle').first().click()
    cy.get(`input[name="${nb(1, BF.text)}"]`).clear().type('Ligne 2')
    cy.get(`select[name="${nb(1, BF.select)}"]`).select('v1')

    saveTermAndReload()

    cy.get(`input[name="${nb(0, BF.text)}"]`).should('have.value', 'Ligne 1')
    cy.get(`input[name="${nb(0, BF.toggle)}"]`).should('be.checked')
    cy.get(`input[name="${nb(0, BF.yesno)}"][value="yes"]`).should('be.checked')
    cy.get(`input[name="${nb(0, BF.radios)}[]"][value="v2"]`).should('be.checked')
    cy.get(`input[name="${nb(0, BF.checkboxes)}[]"][value="v1"]`).should('be.checked')
    cy.get(`input[name="${nb(0, BF.checkboxes)}[]"][value="v3"]`).should('be.checked')
    cy.get(`select[name="${nb(0, BF.select)}"]`).should('have.value', 'v3')
    cy.get(`input[name="${nb(1, BF.text)}"]`).should('have.value', 'Ligne 2')
    cy.get(`select[name="${nb(1, BF.select)}"]`).should('have.value', 'v1')
  })

  it('saves and restores all Tabs Onglet A fields', () => {
    createAndEditCategory('Cypress — Term Tabs')

    cy.contains('Catégorie — Tabs').should('exist')
    cy.get('.cfdev-tabs ul').should('contain', 'Onglet A')

    cy.get(`input[name="${TA.text}"]`).clear().type('TermTabText')
    cy.get(`textarea[name="${TA.textarea}"]`).clear().type('Term tab textarea')
    cy.get(`input[name="${TA.qty}"]`).clear().type('88')
    cy.get(`input[name="${TA.range}"]`).invoke('val', '30').trigger('change')
    cy.get(`input[name="${TA.email}"]`).clear().type('termtab@cypress.io')
    cy.get(`input[name="${TA.url}"]`).clear().type('https://termtab.cypress.io')
    cy.get(`input[name="${TA.tel}"]`).clear().type('+33 3 11 22 33 44')
    cy.get(`input[name="${TA.color}"]`).invoke('val', '#667788').trigger('change', { force: true })
    cy.get(`input[name="${TA.date}"]`).invoke('val', '07/14/2026').trigger('change', { force: true })
    cy.get(`input[name="${TA.toggle}"]`).check()
    cy.get(`input[name="${TA.checkbox}"]`).check()
    cy.get(`input[name="${TA.yesno}"][value="yes"]`).check()
    cy.get(`input[name="${TA.radios}"][value="v3"]`).check()
    cy.get(`select[name="${TA.select}"]`).select('v2')
    cy.get(`input[name="${TA.checkboxes}"][value="v1"]`).check()
    cy.get(`input[name="${TA.checkboxes}"][value="v2"]`).check()

    saveTermAndReload()

    cy.get(`input[name="${TA.text}"]`).should('have.value', 'TermTabText')
    cy.get(`textarea[name="${TA.textarea}"]`).should('have.value', 'Term tab textarea')
    cy.get(`input[name="${TA.qty}"]`).should('have.value', '88')
    cy.get(`input[name="${TA.range}"]`).should('have.value', '30')
    cy.get(`input[name="${TA.email}"]`).should('have.value', 'termtab@cypress.io')
    cy.get(`input[name="${TA.url}"]`).should('have.value', 'https://termtab.cypress.io')
    cy.get(`input[name="${TA.tel}"]`).should('have.value', '+33 3 11 22 33 44')
    cy.get(`input[name="${TA.color}"]`).should('have.value', '#667788')
    cy.get(`input[name="${TA.date}"]`).should('have.value', '07/14/2026')
    cy.get(`input[name="${TA.toggle}"]`).should('be.checked')
    cy.get(`input[name="${TA.checkbox}"]`).should('be.checked')
    cy.get(`input[name="${TA.yesno}"][value="yes"]`).should('be.checked')
    cy.get(`input[name="${TA.radios}"][value="v3"]`).should('be.checked')
    cy.get(`input[name="${TA.radios}"][value="v1"]`).should('not.be.checked')
    cy.get(`select[name="${TA.select}"]`).should('have.value', 'v2')
    cy.get(`input[name="${TA.checkboxes}"][value="v1"]`).should('be.checked')
    cy.get(`input[name="${TA.checkboxes}"][value="v2"]`).should('be.checked')
    cy.get(`input[name="${TA.checkboxes}"][value="v3"]`).should('not.be.checked')
  })

  it('saves Accordion Infos fields and Galerie bundle row 0', () => {
    createAndEditCategory('Cypress — Term Accordion')

    cy.contains('Catégorie — Accordéon With Bundle').should('exist')
    cy.get('.js-cfdev-accordion').within(() => {
      cy.contains('h3', 'Infos').should('exist')
      cy.contains('h3', 'Galerie').should('exist')
    })

    // Fill Infos section (expanded by default)
    cy.get(`input[name="${AI.text}"]`).clear().type('TermAccText')
    cy.get(`textarea[name="${AI.textarea}"]`).clear().type('Term acc textarea')
    cy.get(`input[name="${AI.qty}"]`).clear().type('66')
    cy.get(`input[name="${AI.range}"]`).invoke('val', '40').trigger('change')
    cy.get(`input[name="${AI.email}"]`).clear().type('termacc@cypress.io')
    cy.get(`input[name="${AI.url}"]`).clear().type('https://termacc.cypress.io')
    cy.get(`input[name="${AI.tel}"]`).clear().type('+33 9 11 22 33 44')
    cy.get(`input[name="${AI.color}"]`).invoke('val', '#aabbcc').trigger('change', { force: true })
    cy.get(`input[name="${AI.date}"]`).invoke('val', '08/08/2026').trigger('change', { force: true })
    cy.get(`input[name="${AI.toggle}"]`).check()
    cy.get(`input[name="${AI.checkbox}"]`).check()
    cy.get(`input[name="${AI.yesno}"][value="yes"]`).check()
    cy.get(`input[name="${AI.radios}"][value="v2"]`).check()
    cy.get(`select[name="${AI.select}"]`).select('v1')
    cy.get(`input[name="${AI.checkboxes}"][value="v2"]`).check()
    cy.get(`input[name="${AI.checkboxes}"][value="v3"]`).check()

    // Expand Galerie section and fill its bundle row 0
    cy.get('.js-cfdev-accordion').contains('h3', 'Galerie').click()
    cy.get(`input[name="${nb(0, ACCB.text)}"]`).clear().type('TermAccBundleText')
    cy.get(`select[name="${nb(0, ACCB.select)}"]`).select('v2')

    saveTermAndReload()

    // Verify Infos fields (visible by default after reload)
    cy.get(`input[name="${AI.text}"]`).should('have.value', 'TermAccText')
    cy.get(`textarea[name="${AI.textarea}"]`).should('have.value', 'Term acc textarea')
    cy.get(`input[name="${AI.qty}"]`).should('have.value', '66')
    cy.get(`input[name="${AI.range}"]`).should('have.value', '40')
    cy.get(`input[name="${AI.email}"]`).should('have.value', 'termacc@cypress.io')
    cy.get(`input[name="${AI.url}"]`).should('have.value', 'https://termacc.cypress.io')
    cy.get(`input[name="${AI.tel}"]`).should('have.value', '+33 9 11 22 33 44')
    cy.get(`input[name="${AI.color}"]`).should('have.value', '#aabbcc')
    cy.get(`input[name="${AI.date}"]`).should('have.value', '08/08/2026')
    cy.get(`input[name="${AI.toggle}"]`).should('be.checked')
    cy.get(`input[name="${AI.checkbox}"]`).should('be.checked')
    cy.get(`input[name="${AI.yesno}"][value="yes"]`).should('be.checked')
    cy.get(`input[name="${AI.radios}"][value="v2"]`).should('be.checked')
    cy.get(`input[name="${AI.radios}"][value="v1"]`).should('not.be.checked')
    cy.get(`select[name="${AI.select}"]`).should('have.value', 'v1')
    cy.get(`input[name="${AI.checkboxes}"][value="v2"]`).should('be.checked')
    cy.get(`input[name="${AI.checkboxes}"][value="v3"]`).should('be.checked')
    cy.get(`input[name="${AI.checkboxes}"][value="v1"]`).should('not.be.checked')

    // Expand Galerie and verify its bundle
    cy.get('.js-cfdev-accordion').contains('h3', 'Galerie').click()
    cy.get(`input[name="${nb(0, ACCB.text)}"]`).should('have.value', 'TermAccBundleText')
    cy.get(`select[name="${nb(0, ACCB.select)}"]`).should('have.value', 'v2')
  })
})
