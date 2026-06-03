<?php

namespace Weblitzer\CFDev\Tests\Integration\Meta;

use Weblitzer\CFDev\Meta\TermMeta;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie la condition onlyIfParent() sur TermMeta.
 * La condition limite la sauvegarde aux termes dont le parent direct correspond.
 */
class TermMetaConditionsTest extends IntegrationTestCase
{
    private int $parent_id;
    private int $child_id;
    private int $other_child_id;

    public function setUp(): void
    {
        parent::setUp();
        $admin_id = static::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);

        register_cfdev_taxonomy(['region', 'regions'], 'post', ['public' => true]);
        do_action('init');

        Registry::reset();

        // Hiérarchie : France → Île-de-France, Bretagne
        $ins = wp_insert_term('France', 'region');
        if (is_wp_error($ins)) {
            throw new \RuntimeException($ins->get_error_message()); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
        $this->parent_id = $ins['term_id'];

        $ins = wp_insert_term('Ile-de-France', 'region', ['parent' => $this->parent_id]);
        if (is_wp_error($ins)) {
            throw new \RuntimeException($ins->get_error_message()); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
        $this->child_id = $ins['term_id'];

        $ins = wp_insert_term('Bretagne', 'region', ['parent' => $this->parent_id]);
        if (is_wp_error($ins)) {
            throw new \RuntimeException($ins->get_error_message()); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
        $this->other_child_id = $ins['term_id'];
    }

    public function tearDown(): void
    {
        $_POST = [];
        Registry::reset();
        parent::tearDown();
    }

    /** @param array<string, mixed> $values */
    private function postWith(array $values): void
    {
        $_POST['cfdev_nonce'] = wp_create_nonce('cfdev_meta');
        $_POST['cfdev']       = array_merge(['__activate' => ''], $values);
    }

    // -------------------------------------------------------------------------
    // onlyIfParent() — comportement à la sauvegarde
    // -------------------------------------------------------------------------

    public function testOnlyIfParentSavesWhenParentMatches(): void
    {
        $tm = (new TermMeta('region', 'Région', [
            ['type' => 'text', 'id' => 'capitale_region', 'label' => 'Capitale'],
        ]))->onlyIfParent($this->parent_id);

        $this->postWith(['capitale_region' => 'Paris']);
        $tm->saveTerm($this->child_id);

        $this->assertSame('Paris', get_term_meta($this->child_id, 'capitale_region', true));
    }

    public function testOnlyIfParentSkipsWhenParentDiffers(): void
    {
        // Crée un terme sans parent (terme racine)
        $ins = wp_insert_term('Espagne', 'region');
        if (is_wp_error($ins)) {
            $this->fail($ins->get_error_message());
        }
        $root_id = $ins['term_id'];

        $tm = (new TermMeta('region', 'Région', [
            ['type' => 'text', 'id' => 'capitale_esp', 'label' => 'Capitale'],
        ]))->onlyIfParent($this->parent_id);

        $this->postWith(['capitale_esp' => 'Madrid']);
        $tm->saveTerm($root_id);

        $this->assertSame('', get_term_meta($root_id, 'capitale_esp', true));
    }

    public function testOnlyIfParentSkipsForParentTermItself(): void
    {
        // Le terme parent lui-même ne doit pas être sauvé (son parent est 0)
        $tm = (new TermMeta('region', 'Région', [
            ['type' => 'text', 'id' => 'info_parent', 'label' => 'Info'],
        ]))->onlyIfParent($this->parent_id);

        $this->postWith(['info_parent' => 'valeur']);
        $tm->saveTerm($this->parent_id);

        $this->assertSame('', get_term_meta($this->parent_id, 'info_parent', true));
    }

    public function testOnlyIfParentWorksForMultipleChildren(): void
    {
        $tm = (new TermMeta('region', 'Région', [
            ['type' => 'text', 'id' => 'prefet_region', 'label' => 'Préfet'],
        ]))->onlyIfParent($this->parent_id);

        $this->postWith(['prefet_region' => 'M. Dupont']);
        $tm->saveTerm($this->child_id);

        $this->postWith(['prefet_region' => 'Mme. Martin']);
        $tm->saveTerm($this->other_child_id);

        $this->assertSame('M. Dupont', get_term_meta($this->child_id, 'prefet_region', true));
        $this->assertSame('Mme. Martin', get_term_meta($this->other_child_id, 'prefet_region', true));
    }

    // -------------------------------------------------------------------------
    // onlyIfParent() — condition dans le Registry
    // -------------------------------------------------------------------------

    public function testOnlyIfParentConditionAppearsInRegistry(): void
    {
        (new TermMeta('region', 'Région', [
            ['type' => 'text', 'id' => 'champ_cond', 'label' => 'Champ'],
        ]))->onlyIfParent($this->parent_id);

        $entry = Registry::all()[0];

        $this->assertArrayHasKey('conditions', $entry);
        $this->assertSame($this->parent_id, $entry['conditions']['parent_id'] ?? null);
    }

    public function testNoConditionGivesEmptyConditionsArray(): void
    {
        new TermMeta('region', 'Région', [
            ['type' => 'text', 'id' => 'champ_libre', 'label' => 'Champ'],
        ]);

        $entry = Registry::all()[0];

        $this->assertSame([], $entry['conditions']);
    }

    // -------------------------------------------------------------------------
    // onlyForId()
    // -------------------------------------------------------------------------

    public function testOnlyForIdSavesWhenIdsMatch(): void
    {
        $tm = (new TermMeta('region', 'Région', [
            ['type' => 'text', 'id' => 'champ_id', 'label' => 'Champ'],
        ]))->onlyForId($this->child_id);

        $this->postWith(['champ_id' => 'valeur exacte']);
        $tm->saveTerm($this->child_id);

        $this->assertSame('valeur exacte', get_term_meta($this->child_id, 'champ_id', true));
    }

    public function testOnlyForIdSkipsWhenIdsDiffer(): void
    {
        $tm = (new TermMeta('region', 'Région', [
            ['type' => 'text', 'id' => 'champ_id_other', 'label' => 'Champ'],
        ]))->onlyForId($this->child_id);

        $this->postWith(['champ_id_other' => 'ne doit pas sauver']);
        $tm->saveTerm($this->other_child_id);

        $this->assertSame('', get_term_meta($this->other_child_id, 'champ_id_other', true));
    }

    public function testOnlyForIdSkipsParentTerm(): void
    {
        $tm = (new TermMeta('region', 'Région', [
            ['type' => 'text', 'id' => 'champ_id_parent', 'label' => 'Champ'],
        ]))->onlyForId($this->child_id);

        $this->postWith(['champ_id_parent' => 'ne doit pas sauver']);
        $tm->saveTerm($this->parent_id);

        $this->assertSame('', get_term_meta($this->parent_id, 'champ_id_parent', true));
    }

    public function testOnlyForIdConditionAppearsInRegistry(): void
    {
        (new TermMeta('region', 'Région', [
            ['type' => 'text', 'id' => 'champ_reg_id', 'label' => 'Champ'],
        ]))->onlyForId($this->child_id);

        $entry = Registry::all()[0];

        $this->assertArrayHasKey('conditions', $entry);
        $this->assertSame($this->child_id, $entry['conditions']['term_id'] ?? null);
    }
}
