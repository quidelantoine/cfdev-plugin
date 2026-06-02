/**
 * Tests CFDev Options Pages — sections 16-20 of the demo.
 *
 * Pages enregistrées par la démo :
 *   cfdev_options_demo       — page principale, tous les champs plats (rest:true)
 *   cfdev_options_bundle     — sous-page bundle (id: _opt_bundle)
 *   cfdev_options_tabs       — sous-page tabs (onglets plats + bundle dans tab)
 *   cfdev_options_accordion  — sous-page accordion (required + bundle dans section)
 *   cfdev_options_reglages   — sous-menu Réglages WP (rest:true sur tous les champs)
 *
 * Couverture :
 *   1. Dashboard CFDev — onglet Options : structure + bouton Edit (pas Inspect)
 *   2. Sauvegarde et restauration — page plate (cfdev_options_demo)
 *   3. Sauvegarde et restauration — layout bundle (cfdev_options_bundle)
 *   4. Sauvegarde et restauration — layout tabs (cfdev_options_tabs)
 *   5. Validation + erreur inline — layout accordion (cfdev_options_accordion)
 *   6. Sauvegarde et restauration — sous-menu Réglages (cfdev_options_reglages)
 *   7. Code modal — snippet get_option(), pas de CacheManager
 *   8. REST — GET /cfdev/v1/options/{page_id} renvoie les valeurs enregistrées
 *   9. REST — 404 pour page inconnue ou sans champ rest:true
 */

// ── Constantes de champs ───────────────────────────────────────────────────────

// Page principale plate (generateArrayAllField('opt', 'main'))
const MAIN_TEXT   = 'cfdev[_text_opt_main_text]'
const MAIN_SELECT = 'cfdev[_text_opt_main_select]'
const MAIN_TOGGLE = 'cfdev[_text_opt_main_toggle]'

// Bundle page (id: _opt_bundle ; champs: generateArrayAllField('opt', 'bundle'))
const BUNDLE_ID   = '_opt_bundle'
const BF_TEXT     = '_text_opt_bundle_text'
const BF_SELECT   = '_text_opt_bundle_select'
const nb          = (row, fieldId) => `cfdev[${BUNDLE_ID}][${row}][${fieldId}]`

// Tabs page — onglet "Champs plats"
const TAB_TEXT    = 'cfdev[_opt_tab_a_text]'
const TAB_SELECT  = 'cfdev[_opt_tab_a_select]'

// Accordion page — section "Informations générales"
const ACC_REQUIRED = 'cfdev[_opt_acc_site_name]'
const ACC_EMAIL    = 'cfdev[_opt_acc_site_email]'

// Sous Réglages
const RGL_NAME    = 'cfdev[_opt_rgl_site_name]'
const RGL_TAGLINE = 'cfdev[_opt_rgl_tagline]'

// ── Helpers ───────────────────────────────────────────────────────────────────

function submitOptions() {
  cy.get('#submit').click()
}

function expectSaved() {
  cy.get('.notice-success', { timeout: 15000 }).should('contain', 'Settings saved')
}

// ═══════════════════════════════════════════════════════════════════════════════
// 1. Dashboard CFDev — onglet Options
// ═══════════════════════════════════════════════════════════════════════════════

describe('CFDev — Dashboard: onglet Options', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/admin.php?page=cfdev')
  })

  it('affiche un onglet Options dans la nav du registry', () => {
    cy.get('.cfdev-tabs-nav a[href="#cfdev-tab-options"]').should('exist')
  })

  it('le panneau Options liste au moins une page d\'options', () => {
    cy.get('a[href="#cfdev-tab-options"]').click()
    cy.get('#cfdev-tab-options').should('be.visible')
    cy.get('#cfdev-tab-options .cfdev-group').should('have.length.gte', 1)
  })

  it('les groupes option affichent un bouton Edit (pas Inspect)', () => {
    cy.get('a[href="#cfdev-tab-options"]').click()
    cy.get('#cfdev-tab-options .cfdev-group').first().within(() => {
      cy.get('.cfdev-btn-inspect').should('not.exist')
      cy.get('a.button').should('exist')
    })
  })

  it('le bouton Edit pointe vers la bonne page d\'options', () => {
    cy.get('a[href="#cfdev-tab-options"]').click()
    cy.get('#cfdev-tab-options')
      .contains('.cfdev-group-id', 'cfdev_options_demo')
      .closest('.cfdev-group')
      .find('a.button')
      .should('have.attr', 'href')
      .and('include', 'cfdev-cfdev_options_demo')
  })

  it('le compteur de l\'onglet Options correspond au nombre de groupes', () => {
    cy.get('a[href="#cfdev-tab-options"]').click()
    cy.get('#cfdev-tab-options .cfdev-group').then(($groups) => {
      const count = $groups.length
      cy.get('a[href="#cfdev-tab-options"] .cfdev-tab-count')
        .invoke('text')
        .then((text) => {
          expect(parseInt(text)).to.eq(count)
        })
    })
  })
})

// ═══════════════════════════════════════════════════════════════════════════════
// 2. Page plate — cfdev_options_demo
// ═══════════════════════════════════════════════════════════════════════════════

describe('CFDev — Options page plate (cfdev_options_demo)', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/admin.php?page=cfdev-cfdev_options_demo')
  })

  it('affiche le titre, la description et le formulaire', () => {
    cy.get('.wrap h1').should('contain', '[DEMO] Options')
    cy.get('.cfdev-description').should('contain', 'globaux')
    cy.get('form').should('exist')
    cy.get('#submit').should('exist')
  })

  it('sauvegarde et restaure un champ texte', () => {
    const val = 'CypressMainText' + Date.now()
    cy.get(`input[name="${MAIN_TEXT}"]`).clear().type(val)
    submitOptions()
    expectSaved()
    cy.get(`input[name="${MAIN_TEXT}"]`).should('have.value', val)
  })

  it('sauvegarde et restaure un champ select', () => {
    cy.get(`select[name="${MAIN_SELECT}"]`).select('v2')
    submitOptions()
    expectSaved()
    cy.get(`select[name="${MAIN_SELECT}"]`).should('have.value', 'v2')
  })

  it('sauvegarde et restaure un toggle', () => {
    cy.get(`input[name="${MAIN_TOGGLE}"]`).check()
    submitOptions()
    expectSaved()
    cy.get(`input[name="${MAIN_TOGGLE}"]`).should('be.checked')
  })

  it('l\'URL après sauvegarde contient cfdev-updated=1', () => {
    cy.get(`input[name="${MAIN_TEXT}"]`).clear().type('check-url')
    submitOptions()
    cy.url({ timeout: 15000 }).should('include', 'cfdev-updated=1')
  })
})

// ═══════════════════════════════════════════════════════════════════════════════
// 3. Page bundle — cfdev_options_bundle
// ═══════════════════════════════════════════════════════════════════════════════

describe('CFDev — Options bundle (cfdev_options_bundle)', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/admin.php?page=cfdev-cfdev_options_bundle')
  })

  it('affiche le bundle avec son bouton "Ajouter"', () => {
    cy.get('.js-cfdev-add-bundle').should('be.visible')
  })

  // L'état initial dépend du DB (runs précédents). On teste le delta +1/-1.
  it('ajouter une ligne puis supprimer revient au nombre initial', () => {
    cy.get('.js-cfdev-sortable-item').its('length').then((initial) => {
      cy.get('.js-cfdev-add-bundle').click()
      cy.get('.js-cfdev-sortable-item').should('have.length', initial + 1)
      cy.get('.js-cfdev-remove-sortable').last().click()
      cy.get('.js-cfdev-sortable-item').should('have.length', initial)
    })
  })

  it('sauvegarde et restaure le texte d\'une ligne de bundle', () => {
    const val = 'BundleOptRow' + Date.now()
    cy.get(`input[name="${nb(0, BF_TEXT)}"]`).clear().type(val)
    submitOptions()
    expectSaved()
    cy.get(`input[name="${nb(0, BF_TEXT)}"]`).should('have.value', val)
  })

  // Réduit à 1 ligne avant d'en ajouter une 2e, pour éviter la pollution d'état.
  // On cible par position DOM (.eq) car l'index dans le name peut ne pas commencer à 0
  // si des lignes ont été supprimées lors d'un run précédent.
  it('sauvegarde plusieurs lignes', () => {
    Cypress._.times(10, () => {
      cy.get('body').then(($b) => {
        if ($b.find('.js-cfdev-remove-sortable').length > 0) {
          cy.get('.js-cfdev-remove-sortable').first().click()
        }
      })
    })
    cy.get('.js-cfdev-add-bundle').click()
    cy.get('.js-cfdev-sortable-item').should('have.length', 2)
    cy.get('.js-cfdev-sortable-item').eq(0).find(`input[name*="${BF_TEXT}"]`).clear().type('Ligne0')
    cy.get('.js-cfdev-sortable-item').eq(1).find(`input[name*="${BF_TEXT}"]`).clear().type('Ligne1')
    submitOptions()
    expectSaved()
    cy.get('.js-cfdev-sortable-item').eq(0).find(`input[name*="${BF_TEXT}"]`).should('have.value', 'Ligne0')
    cy.get('.js-cfdev-sortable-item').eq(1).find(`input[name*="${BF_TEXT}"]`).should('have.value', 'Ligne1')
  })
})

// ═══════════════════════════════════════════════════════════════════════════════
// 4. Page tabs — cfdev_options_tabs
// ═══════════════════════════════════════════════════════════════════════════════

describe('CFDev — Options tabs (cfdev_options_tabs)', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/admin.php?page=cfdev-cfdev_options_tabs')
  })

  it('affiche les deux onglets', () => {
    cy.contains('.js-cfdev-tabs li a', 'Champs Plats').should('exist')
    cy.contains('.js-cfdev-tabs li a', 'Bundle Dans Tab').should('exist')
  })

  it('bascule vers l\'onglet "Bundle dans tab" et affiche le bundle', () => {
    cy.contains('.js-cfdev-tabs li a', 'Bundle Dans Tab').click()
    cy.get('.js-cfdev-add-bundle').should('be.visible')
  })

  it('sauvegarde un champ texte dans l\'onglet "Champs plats"', () => {
    cy.contains('.js-cfdev-tabs li a', 'Champs Plats').click()
    const val = 'TabTextOpt' + Date.now()
    cy.get(`input[name="${TAB_TEXT}"]`).clear().type(val)
    submitOptions()
    expectSaved()
    cy.contains('.js-cfdev-tabs li a', 'Champs Plats').click()
    cy.get(`input[name="${TAB_TEXT}"]`).should('have.value', val)
  })

  it('sauvegarde un select dans l\'onglet "Champs plats"', () => {
    cy.contains('.js-cfdev-tabs li a', 'Champs Plats').click()
    cy.get(`select[name="${TAB_SELECT}"]`).select('v3')
    submitOptions()
    expectSaved()
    cy.contains('.js-cfdev-tabs li a', 'Champs Plats').click()
    cy.get(`select[name="${TAB_SELECT}"]`).should('have.value', 'v3')
  })
})

// ═══════════════════════════════════════════════════════════════════════════════
// 5. Validation — cfdev_options_accordion
// ═══════════════════════════════════════════════════════════════════════════════

describe('CFDev — Options accordion — validation', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/admin.php?page=cfdev-cfdev_options_accordion')
    // Ouvrir la section contenant le champ requis
    cy.get('.js-cfdev-accordion h3').first().click()
  })

  it('affiche les sections accordion', () => {
    cy.get('.js-cfdev-accordion h3').should('have.length.gte', 3)
  })

  it('affiche une notice-error si le champ requis est vide', () => {
    cy.get(`input[name="${ACC_REQUIRED}"]`).clear()
    submitOptions()
    cy.get('.notice-error, .cfdev-field-error', { timeout: 15000 }).should('exist')
  })

  it('marque la ligne du champ requis avec .cfdev-has-error', () => {
    cy.get(`input[name="${ACC_REQUIRED}"]`).clear()
    submitOptions()
    cy.url({ timeout: 15000 }).should('not.include', 'cfdev-updated')
    cy.get('.js-cfdev-accordion h3').first().click()
    cy.get(`input[name="${ACC_REQUIRED}"]`)
      .closest('tr')
      .should('have.class', 'cfdev-has-error')
  })

  it('sauvegarde avec succès quand le champ requis est rempli', () => {
    cy.get(`input[name="${ACC_REQUIRED}"]`).clear().type('Mon site CFDev')
    submitOptions()
    expectSaved()
  })

  it('efface l\'erreur une fois le champ rempli et sauvegardé', () => {
    // Déclencher l'erreur
    cy.get(`input[name="${ACC_REQUIRED}"]`).clear()
    submitOptions()
    cy.url({ timeout: 15000 }).should('not.include', 'cfdev-updated')

    // Corriger le champ et sauvegarder
    cy.get('.js-cfdev-accordion h3').first().click()
    cy.get(`input[name="${ACC_REQUIRED}"]`).clear().type('Site corrigé')
    submitOptions()
    expectSaved()

    cy.get('.cfdev-has-error').should('not.exist')
  })
})

// ═══════════════════════════════════════════════════════════════════════════════
// 6. Sous-menu Réglages — cfdev_options_reglages
// ═══════════════════════════════════════════════════════════════════════════════

describe('CFDev — Options sous Réglages (cfdev_options_reglages)', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/options-general.php?page=cfdev-cfdev_options_reglages')
  })

  it('affiche le titre et le formulaire', () => {
    cy.get('.wrap h1').should('contain', '[DEMO] Options')
    cy.get('form').should('exist')
    cy.get(`input[name="${RGL_NAME}"]`).should('exist')
  })

  it('sauvegarde et restaure les champs plats', () => {
    const name    = 'Agence Cypress ' + Date.now()
    const tagline = 'Tagline ' + Date.now()
    cy.get(`input[name="${RGL_NAME}"]`).clear().type(name)
    cy.get(`input[name="${RGL_TAGLINE}"]`).clear().type(tagline)
    submitOptions()
    expectSaved()
    cy.get(`input[name="${RGL_NAME}"]`).should('have.value', name)
    cy.get(`input[name="${RGL_TAGLINE}"]`).should('have.value', tagline)
  })

  it('affiche une erreur si le champ requis est vide', () => {
    cy.get(`input[name="${RGL_NAME}"]`).clear()
    submitOptions()
    cy.get('.notice-error, .cfdev-field-error', { timeout: 15000 }).should('exist')
  })

  it('l\'URL après sauvegarde reste sous options-general.php', () => {
    cy.get(`input[name="${RGL_NAME}"]`).clear().type('TestURL')
    submitOptions()
    cy.url({ timeout: 15000 }).should('include', 'options-general.php')
    cy.url().should('include', 'cfdev-updated=1')
  })
})

// ═══════════════════════════════════════════════════════════════════════════════
// 7. Code modal — snippet option
// ═══════════════════════════════════════════════════════════════════════════════

describe('CFDev — Code modal pour les options', () => {
  beforeEach(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/admin.php?page=cfdev')
    cy.get('a[href="#cfdev-tab-options"]').click()
    cy.get('#cfdev-tab-options').should('be.visible')
  })

  it('le bouton Code est présent sur les groupes option', () => {
    cy.get('#cfdev-tab-options .cfdev-group').first().within(() => {
      cy.get('.cfdev-btn-code').should('exist')
    })
  })

  it('le modal Code s\'ouvre sur un groupe option', () => {
    cy.get('#cfdev-tab-options')
      .contains('.cfdev-group-id', 'cfdev_options_demo')
      .closest('.cfdev-group')
      .find('.cfdev-btn-code')
      .click()

    cy.get('#cfdev-code-modal').should('be.visible')
    cy.get('#cfdev-code-group-id').should('contain', 'cfdev_options_demo')
  })

  it('le snippet Display contient get_option() et pas de CacheManager', () => {
    cy.get('#cfdev-tab-options')
      .contains('.cfdev-group-id', 'cfdev_options_demo')
      .closest('.cfdev-group')
      .find('.cfdev-btn-code')
      .click()

    cy.get('#cfdev-code-tab-display').should('have.class', 'is-active')

    cy.get('#cfdev-code-output').invoke('text').then((code) => {
      expect(code).to.include('get_option')
      expect(code).to.include('<?php')
      expect(code).not.to.include('CacheManager')
    })
  })

  it('le snippet Raw n\'a pas d\'echo ni de balises HTML', () => {
    cy.get('#cfdev-tab-options')
      .contains('.cfdev-group-id', 'cfdev_options_demo')
      .closest('.cfdev-group')
      .find('.cfdev-btn-code')
      .click()

    cy.get('#cfdev-code-tab-raw').click()
    cy.get('#cfdev-code-output').invoke('text').then((code) => {
      expect(code).not.to.include('echo ')
      expect(code).not.to.include('<a ')
      expect(code).not.to.include('<img')
    })
  })

  it('le snippet d\'un bundle option utilise Field::decodeMetaValue(get_option(...))', () => {
    cy.get('#cfdev-tab-options')
      .contains('.cfdev-group-id', 'cfdev_options_bundle')
      .closest('.cfdev-group')
      .find('.cfdev-btn-code')
      .click()

    cy.get('#cfdev-code-output').invoke('text').then((code) => {
      expect(code).to.include('get_option')
      expect(code).to.include('decodeMetaValue')
      expect(code).to.include('foreach')
    })
  })
})

// ═══════════════════════════════════════════════════════════════════════════════
// 8-9. REST API — endpoint /cfdev/v1/options/{page_id}
// ═══════════════════════════════════════════════════════════════════════════════

describe('CFDev — REST API options', () => {
  // Sauvegarder une valeur connue avant les tests REST
  before(() => {
    cy.loginToWP()
    cy.visit('/wp-admin/options-general.php?page=cfdev-cfdev_options_reglages')
    cy.get(`input[name="${RGL_NAME}"]`).clear().type('Agence REST Cypress')
    cy.get(`input[name="${RGL_TAGLINE}"]`).clear().type('Slogan REST')
    cy.get('#submit').click()
    cy.get('.notice-success', { timeout: 15000 }).should('exist')
  })

  it('GET /cfdev/v1/options/cfdev_options_reglages retourne 200 avec les valeurs', () => {
    cy.request('/wp-json/cfdev/v1/options/cfdev_options_reglages').then((res) => {
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
    cy.request('/wp-json/cfdev/v1/options/cfdev_options_reglages').then((res) => {
      const group = res.body.groups?.cfdev_options_reglages ?? {}
      // _opt_rgl_address et _opt_rgl_logo sont rest:false → absents
      expect(group).not.to.have.property('_opt_rgl_address')
      expect(group).not.to.have.property('_opt_rgl_logo')
    })
  })

  it('GET /cfdev/v1/options/inexistant retourne 404', () => {
    cy.request({
      url: '/wp-json/cfdev/v1/options/inexistant_xyz',
      failOnStatusCode: false,
    }).then((res) => {
      expect(res.status).to.eq(404)
    })
  })

  it('GET /cfdev/v1/options/cfdev_options_demo retourne les champs plats avec valeurs', () => {
    cy.loginToWP()
    // Sauvegarder une valeur de référence
    cy.visit('/wp-admin/admin.php?page=cfdev-cfdev_options_demo')
    cy.get(`input[name="${MAIN_TEXT}"]`).clear().type('REST_Demo_Text')
    cy.get('#submit').click()
    cy.get('.notice-success', { timeout: 15000 }).should('exist')

    cy.request('/wp-json/cfdev/v1/options/cfdev_options_demo').then((res) => {
      expect(res.status).to.eq(200)
      const group = res.body.groups?.cfdev_options_demo ?? {}
      expect(group).to.have.property('_text_opt_main_text', 'REST_Demo_Text')
    })
  })

  it('GET /cfdev/v1/options/cfdev_options_bundle retourne les lignes du bundle décodées', () => {
    cy.loginToWP()
    cy.visit('/wp-admin/admin.php?page=cfdev-cfdev_options_bundle')
    cy.get(`input[name="${nb(0, BF_TEXT)}"]`).clear().type('BundleREST')
    cy.get('#submit').click()
    cy.get('.notice-success', { timeout: 15000 }).should('exist')

    cy.request('/wp-json/cfdev/v1/options/cfdev_options_bundle').then((res) => {
      expect(res.status).to.eq(200)
      const group  = res.body.groups?.cfdev_options_bundle ?? {}
      const bundle = group[BUNDLE_ID]
      expect(bundle).to.be.an('array').with.length.gte(1)
      expect(bundle[0]).to.have.property(BF_TEXT, 'BundleREST')
    })
  })
})