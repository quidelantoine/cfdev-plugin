<?php

namespace Weblitzer\CFDev\Tests\Integration\Registry;

use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;
use Weblitzer\CFDev\Validation\Rules\Contains;
use Weblitzer\CFDev\Validation\Rules\MaxLength;
use Weblitzer\CFDev\Validation\Rules\MinLength;

/**
 * Vérifie que Registry::all() expose correctement les layouts complexes
 * (bundle, tabs, accordion) et la sérialisation des règles de validation.
 */
class RegistryLayoutsTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        register_cfdev_post_type(['article', 'articles'], ['public' => true]);
        do_action('init');

        Registry::reset();
    }

    public function tearDown(): void
    {
        Registry::reset();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Bundle layout
    // -------------------------------------------------------------------------

    public function testBundleLayoutIsDetected(): void
    {
        new MetaBox('sessions', 'Sessions', 'article', [
            'bundle', 'items', [
                ['type' => 'text', 'id' => 'titre', 'label' => 'Titre'],
                ['type' => 'text', 'id' => 'lieu',  'label' => 'Lieu'],
            ],
        ]);

        $entry = Registry::all()[0];

        $this->assertSame('bundle', $entry['layout']);
    }

    public function testBundleFieldsAppearInBundlesKey(): void
    {
        new MetaBox('sessions', 'Sessions', 'article', [
            'bundle', 'items', [
                ['type' => 'text', 'id' => 'titre', 'label' => 'Titre'],
                ['type' => 'text', 'id' => 'lieu',  'label' => 'Lieu'],
            ],
        ]);

        $entry = Registry::all()[0];

        $this->assertArrayHasKey('bundles', $entry);
        $this->assertArrayHasKey('_items', $entry['bundles']);
        $this->assertArrayHasKey('titre', $entry['bundles']['_items']['fields']);
        $this->assertArrayHasKey('lieu', $entry['bundles']['_items']['fields']);
    }

    public function testBundleFieldsAbsentFromFlatFields(): void
    {
        new MetaBox('sessions', 'Sessions', 'article', [
            'bundle', 'items', [
                ['type' => 'text', 'id' => 'titre', 'label' => 'Titre'],
            ],
        ]);

        $entry = Registry::all()[0];

        // Les champs in_bundle sont exclus de la map plates fields
        $this->assertArrayNotHasKey('titre', $entry['fields']);
    }

    // -------------------------------------------------------------------------
    // Tabs layout
    // -------------------------------------------------------------------------

    public function testTabsLayoutIsDetected(): void
    {
        new MetaBox('fiche', 'Fiche', 'article', [
            'tabs', [
                'Général'    => [
                    ['type' => 'text', 'id' => 'titre_g', 'label' => 'Titre'],
                ],
                'Avancé'     => [
                    ['type' => 'text', 'id' => 'note_a',  'label' => 'Note'],
                ],
            ],
        ]);

        $entry = Registry::all()[0];

        $this->assertSame('tabs', $entry['layout']);
    }

    public function testTabsSectionsExposeTitlesAndFields(): void
    {
        new MetaBox('fiche', 'Fiche', 'article', [
            'tabs', [
                'Général'    => [
                    ['type' => 'text', 'id' => 'titre_g', 'label' => 'Titre'],
                ],
                'Avancé'     => [
                    ['type' => 'text', 'id' => 'note_a',  'label' => 'Note'],
                ],
            ],
        ]);

        $entry    = Registry::all()[0];
        $sections = $entry['sections'];

        $this->assertCount(2, $sections);
        $this->assertSame('Général', $sections[0]['title']);
        $this->assertSame('Avancé', $sections[1]['title']);
        $this->assertArrayHasKey('titre_g', $sections[0]['fields']);
        $this->assertArrayHasKey('note_a', $sections[1]['fields']);
    }

    public function testTabsSectionContainingBundleExposesBundleId(): void
    {
        new MetaBox('programme', 'Programme', 'article', [
            'tabs', [
                'Séances' => [
                    ['bundle', 'seances_tab', [
                        ['type' => 'text', 'id' => 'heure', 'label' => 'Heure'],
                    ]],
                ],
            ],
        ]);

        $entry    = Registry::all()[0];
        $sections = $entry['sections'];

        $this->assertCount(1, $sections);
        $this->assertSame('_seances_tab', $sections[0]['bundle_id']);
        $this->assertSame([], $sections[0]['fields']);
    }

    public function testTabsBundleAppearsInBundlesKey(): void
    {
        new MetaBox('programme', 'Programme', 'article', [
            'tabs', [
                'Séances' => [
                    ['bundle', 'seances_tab', [
                        ['type' => 'text', 'id' => 'heure', 'label' => 'Heure'],
                    ]],
                ],
            ],
        ]);

        $entry = Registry::all()[0];

        $this->assertArrayHasKey('_seances_tab', $entry['bundles']);
        $this->assertArrayHasKey('heure', $entry['bundles']['_seances_tab']['fields']);
    }

    // -------------------------------------------------------------------------
    // Accordion layout
    // -------------------------------------------------------------------------

    public function testAccordionLayoutIsDetected(): void
    {
        new MetaBox('faq', 'FAQ', 'article', [
            'accordion', [
                'Question 1' => [
                    ['type' => 'text', 'id' => 'reponse_1', 'label' => 'Réponse'],
                ],
            ],
        ]);

        $entry = Registry::all()[0];

        $this->assertSame('accordion', $entry['layout']);
    }

    public function testAccordionSectionsExposeTitlesAndFields(): void
    {
        new MetaBox('faq', 'FAQ', 'article', [
            'accordion', [
                'Question 1' => [
                    ['type' => 'text', 'id' => 'reponse_1', 'label' => 'Réponse 1'],
                ],
                'Question 2' => [
                    ['type' => 'text', 'id' => 'reponse_2', 'label' => 'Réponse 2'],
                ],
            ],
        ]);

        $entry    = Registry::all()[0];
        $sections = $entry['sections'];

        $this->assertCount(2, $sections);
        $this->assertSame('Question 1', $sections[0]['title']);
        $this->assertSame('Question 2', $sections[1]['title']);
        $this->assertArrayHasKey('reponse_1', $sections[0]['fields']);
        $this->assertArrayHasKey('reponse_2', $sections[1]['fields']);
    }

    public function testAccordionSectionContainingBundleExposesBundleId(): void
    {
        new MetaBox('module', 'Module', 'article', [
            'accordion', [
                'Leçons' => [
                    ['bundle', 'lecons_acc', [
                        ['type' => 'text', 'id' => 'titre_lecon', 'label' => 'Titre'],
                        ['type' => 'text', 'id' => 'duree',       'label' => 'Durée'],
                    ]],
                ],
            ],
        ]);

        $entry    = Registry::all()[0];
        $sections = $entry['sections'];

        $this->assertCount(1, $sections);
        $this->assertSame('Leçons', $sections[0]['title']);
        $this->assertSame('_lecons_acc', $sections[0]['bundle_id']);
        $this->assertSame([], $sections[0]['fields']);
    }

    public function testAccordionBundleAppearsInBundlesKey(): void
    {
        new MetaBox('module', 'Module', 'article', [
            'accordion', [
                'Leçons' => [
                    ['bundle', 'lecons_acc', [
                        ['type' => 'text', 'id' => 'titre_lecon', 'label' => 'Titre'],
                        ['type' => 'text', 'id' => 'duree',       'label' => 'Durée'],
                    ]],
                ],
            ],
        ]);

        $entry = Registry::all()[0];

        $this->assertArrayHasKey('_lecons_acc', $entry['bundles']);
        $this->assertArrayHasKey('titre_lecon', $entry['bundles']['_lecons_acc']['fields']);
        $this->assertArrayHasKey('duree', $entry['bundles']['_lecons_acc']['fields']);
    }

    public function testAccordionMixedSectionsFlatAndBundle(): void
    {
        new MetaBox('cours', 'Cours', 'article', [
            'accordion', [
                'Infos'   => [
                    ['type' => 'text', 'id' => 'description_cours', 'label' => 'Description'],
                ],
                'Modules' => [
                    ['bundle', 'modules_acc', [
                        ['type' => 'text', 'id' => 'nom_module', 'label' => 'Nom'],
                    ]],
                ],
            ],
        ]);

        $entry    = Registry::all()[0];
        $sections = $entry['sections'];

        $this->assertCount(2, $sections);

        // Première section : champs plats, pas de bundle_id
        $this->assertNull($sections[0]['bundle_id']);
        $this->assertArrayHasKey('description_cours', $sections[0]['fields']);

        // Deuxième section : bundle, pas de champs plats
        $this->assertSame('_modules_acc', $sections[1]['bundle_id']);
        $this->assertSame([], $sections[1]['fields']);
    }

    // -------------------------------------------------------------------------
    // Validation rules serialization
    // -------------------------------------------------------------------------

    public function testRequiredFlagIsTrueWhenSet(): void
    {
        new MetaBox('val', 'Validation', 'article', [
            ['type' => 'text', 'id' => 'champ_req', 'label' => 'Champ', 'required' => true],
        ]);

        $entry = Registry::all()[0];

        $this->assertTrue($entry['fields']['champ_req']['required']);
    }

    public function testRequiredFlagIsFalseByDefault(): void
    {
        new MetaBox('val', 'Validation', 'article', [
            ['type' => 'text', 'id' => 'champ_opt', 'label' => 'Champ'],
        ]);

        $entry = Registry::all()[0];

        $this->assertFalse($entry['fields']['champ_opt']['required']);
    }

    public function testRequiredRuleNotInRulesArray(): void
    {
        // Required est auto-ajouté par Field mais filtré de 'rules[]' dans Registry
        new MetaBox('val', 'Validation', 'article', [
            ['type' => 'text', 'id' => 'champ_r', 'label' => 'Champ', 'required' => true],
        ]);

        $entry = Registry::all()[0];
        $rules = $entry['fields']['champ_r']['rules'];

        $this->assertEmpty($rules);
    }

    public function testMinMaxLengthRulesSerialized(): void
    {
        new MetaBox('val', 'Validation', 'article', [
            [
                'type'  => 'text',
                'id'    => 'champ_ml',
                'label' => 'Champ',
                'rules' => [new MinLength(5), new MaxLength(100)],
            ],
        ]);

        $entry = Registry::all()[0];
        $rules = $entry['fields']['champ_ml']['rules'];

        $this->assertContains('min-length: 5', $rules);
        $this->assertContains('max-length: 100', $rules);
    }

    public function testMultipleRulesAllSerialized(): void
    {
        new MetaBox('val', 'Validation', 'article', [
            [
                'type'  => 'text',
                'id'    => 'champ_multi',
                'label' => 'Champ',
                'rules' => [new MinLength(2), new MaxLength(50), new Contains('a')],
            ],
        ]);

        $entry = Registry::all()[0];
        $rules = $entry['fields']['champ_multi']['rules'];

        $this->assertCount(3, $rules);
    }

    public function testNoRulesGivesEmptyArray(): void
    {
        new MetaBox('val', 'Validation', 'article', [
            ['type' => 'text', 'id' => 'champ_bare', 'label' => 'Champ'],
        ]);

        $entry = Registry::all()[0];

        $this->assertSame([], $entry['fields']['champ_bare']['rules']);
    }

    // -------------------------------------------------------------------------
    // Field type stored as-is
    // -------------------------------------------------------------------------

    public function testFieldTypeIsStoredInEntry(): void
    {
        new MetaBox('types', 'Types', 'article', [
            ['type' => 'text',     'id' => 'f_text',   'label' => 'Text'],
            ['type' => 'number',   'id' => 'f_number', 'label' => 'Number'],
            ['type' => 'textarea', 'id' => 'f_area',   'label' => 'Textarea'],
        ]);

        $entry = Registry::all()[0];

        $this->assertSame('text', $entry['fields']['f_text']['type']);
        $this->assertSame('number', $entry['fields']['f_number']['type']);
        $this->assertSame('textarea', $entry['fields']['f_area']['type']);
    }

    public function testUnknownFieldTypeIsSilentlySkipped(): void
    {
        new MetaBox('types', 'Types', 'article', [
            ['type' => 'text',              'id' => 'known',   'label' => 'Known'],
            ['type' => 'type_inexistant_x', 'id' => 'unknown', 'label' => 'Unknown'],
        ]);

        $entry = Registry::all()[0];

        $this->assertArrayHasKey('known', $entry['fields']);
        $this->assertArrayNotHasKey('unknown', $entry['fields']);
    }
}
