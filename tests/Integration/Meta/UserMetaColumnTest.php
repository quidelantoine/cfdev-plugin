<?php

namespace Weblitzer\CFDev\Tests\Integration\Meta;

use Weblitzer\CFDev\Meta\UserMeta;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie UserMeta::addColumn() et addColumnContent() avec une vraie BDD WP.
 */
class UserMetaColumnTest extends IntegrationTestCase
{
    private int $user_id;

    public function setUp(): void
    {
        parent::setUp();
        Registry::reset();

        $this->user_id = static::factory()->user->create(['role' => 'editor']);
    }

    public function tearDown(): void
    {
        Registry::reset();
        parent::tearDown();
    }

    private function buildUserMeta(): UserMeta
    {
        return new UserMeta('profil', 'Profil étendu', [
            ['type' => 'text', 'id' => 'metier',    'label' => 'Métier',    'show_admin_column' => true],
            ['type' => 'text', 'id' => 'linkedin',  'label' => 'LinkedIn'],
        ]);
    }

    // -------------------------------------------------------------------------
    // addColumn
    // -------------------------------------------------------------------------

    public function testAddColumnAddsHeaderForShowAdminColumnField(): void
    {
        $um     = $this->buildUserMeta();
        $result = $um->addColumn(['username' => 'Identifiant', 'email' => 'Email']);

        $this->assertArrayHasKey('metier', $result);
        $this->assertSame('Métier', $result['metier']);
    }

    public function testAddColumnSkipsFieldWithoutShowAdminColumn(): void
    {
        $um     = $this->buildUserMeta();
        $result = $um->addColumn(['username' => 'Identifiant', 'email' => 'Email']);

        $this->assertArrayNotHasKey('linkedin', $result);
    }

    public function testAddColumnDoesNotAlterExistingColumns(): void
    {
        $um      = $this->buildUserMeta();
        $initial = ['username' => 'Identifiant', 'email' => 'Email'];
        $result  = $um->addColumn($initial);

        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('email', $result);
    }

    // -------------------------------------------------------------------------
    // addColumnContent
    // -------------------------------------------------------------------------

    public function testAddColumnContentReturnsUserMetaForMatchingColumn(): void
    {
        update_user_meta($this->user_id, 'metier', 'Développeur');

        $um     = $this->buildUserMeta();
        $result = $um->addColumnContent('', 'metier', $this->user_id);

        $this->assertSame('Développeur', $result);
    }

    public function testAddColumnContentReturnsValueUnchangedForUnknownColumn(): void
    {
        $um     = $this->buildUserMeta();
        $result = $um->addColumnContent('fallback', 'colonne_inexistante', $this->user_id);

        $this->assertSame('fallback', $result);
    }

    public function testAddColumnContentReturnsImplodedValueForRepeatableField(): void
    {
        $um = new UserMeta('profil_repet', 'Profil', [
            ['type' => 'text', 'id' => 'competences', 'label' => 'Compétences', 'show_admin_column' => true, 'repeatable' => true],
        ]);

        update_user_meta($this->user_id, 'competences', wp_json_encode(['PHP', 'CSS']));

        $result = $um->addColumnContent('', 'competences', $this->user_id);

        $this->assertSame('PHP, CSS', $result);
    }
}
