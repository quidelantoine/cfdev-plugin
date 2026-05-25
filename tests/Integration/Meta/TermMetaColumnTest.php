<?php

namespace Weblitzer\CFDev\Tests\Integration\Meta;

use Weblitzer\CFDev\Meta\TermMeta;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie TermMeta::addColumn() et addColumnContent() avec une vraie BDD WP.
 *
 * Note : get_current_screen() n'est disponible qu'en contexte admin. On charge
 * les includes WP_Screen une seule fois dans set_up_before_class() et on utilise
 * set_current_screen() pour les tests qui en ont besoin. Le WP_UnitTestCase_Base
 * garantit que $GLOBALS['current_screen'] = null au début de chaque test.
 */
class TermMetaColumnTest extends IntegrationTestCase
{
    private int $term_id;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        if (! class_exists('WP_Screen')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
        }
        if (! function_exists('convert_to_screen')) {
            require_once ABSPATH . 'wp-admin/includes/screen.php';
        }
    }

    public function setUp(): void
    {
        parent::setUp();
        Registry::reset();

        register_cfdev_taxonomy(['genre', 'genres'], 'post', ['public' => true]);
        do_action('init');

        $result = wp_insert_term('Roman', 'genre');
        if (is_wp_error($result)) {
            throw new \RuntimeException($result->get_error_message()); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
        $this->term_id = $result['term_id'];
    }

    public function tearDown(): void
    {
        // parent::setUp() remet $GLOBALS['current_screen'] = null au prochain test
        $GLOBALS['current_screen'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        Registry::reset();
        parent::tearDown();
    }

    private function buildTermMeta(): TermMeta
    {
        return new TermMeta('genre', 'Genre', [
            ['type' => 'text', 'id' => 'origine', 'label' => 'Origine', 'show_admin_column' => true],
            ['type' => 'text', 'id' => 'code',    'label' => 'Code'],
        ]);
    }

    private function setAdminScreen(): void
    {
        set_current_screen('edit-tags');
        global $current_screen;
        if ($current_screen instanceof \WP_Screen) {
            $current_screen->taxonomy = 'genre';
        }
        $GLOBALS['taxnow'] = 'genre'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
    }

    // -------------------------------------------------------------------------
    // addColumn
    // -------------------------------------------------------------------------

    public function testAddColumnAddsHeaderForShowAdminColumnField(): void
    {
        $tm     = $this->buildTermMeta();
        $result = $tm->addColumn(['name' => 'Nom', 'description' => 'Description']);

        $this->assertArrayHasKey('origine', $result);
        $this->assertSame('Origine', $result['origine']);
    }

    public function testAddColumnSkipsFieldWithoutShowAdminColumn(): void
    {
        $tm     = $this->buildTermMeta();
        $result = $tm->addColumn(['name' => 'Nom', 'description' => 'Description']);

        $this->assertArrayNotHasKey('code', $result);
    }

    public function testAddColumnDoesNotAlterExistingColumns(): void
    {
        $tm      = $this->buildTermMeta();
        $initial = ['name' => 'Nom', 'description' => 'Description'];
        $result  = $tm->addColumn($initial);

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('description', $result);
    }

    // -------------------------------------------------------------------------
    // addColumnContent
    // -------------------------------------------------------------------------

    public function testAddColumnContentReturnsRowUnchangedWithoutScreen(): void
    {
        // WP_UnitTestCase_Base::set_up() garantit que $GLOBALS['current_screen'] = null.
        // get_current_screen() retourne null → if ($screen) est false → retourne $row.
        $tm     = $this->buildTermMeta();
        $result = $tm->addColumnContent('valeur_ligne', 'origine', $this->term_id);

        $this->assertSame('valeur_ligne', $result);
    }

    public function testAddColumnContentReturnsTermMetaForMatchingColumn(): void
    {
        $tm = $this->buildTermMeta();
        $_POST['cfdev_nonce'] = wp_create_nonce('cfdev_meta');
        $_POST['cfdev']       = ['__activate' => '', 'origine' => 'Francaise'];
        $tm->saveTerm($this->term_id);
        $_POST = [];

        $this->assertSame('Francaise', get_term_meta($this->term_id, 'origine', true));

        $this->setAdminScreen();

        $result = $tm->addColumnContent('', 'origine', $this->term_id);

        $this->assertSame('Francaise', $result);
    }

    public function testAddColumnContentReturnsRowForUnknownColumn(): void
    {
        $this->setAdminScreen();

        $tm     = $this->buildTermMeta();
        $result = $tm->addColumnContent('fallback', 'colonne_inexistante', $this->term_id);

        $this->assertSame('fallback', $result);
    }
}
