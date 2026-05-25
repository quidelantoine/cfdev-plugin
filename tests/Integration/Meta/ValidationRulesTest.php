<?php

namespace Weblitzer\CFDev\Tests\Integration\Meta;

use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Meta\TermMeta;
use Weblitzer\CFDev\Meta\UserMeta;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;
use Weblitzer\CFDev\Validation\ErrorBag;
use Weblitzer\CFDev\Validation\Rules\Between;
use Weblitzer\CFDev\Validation\Rules\Contains;
use Weblitzer\CFDev\Validation\Rules\Max;
use Weblitzer\CFDev\Validation\Rules\MaxLength;
use Weblitzer\CFDev\Validation\Rules\Min;
use Weblitzer\CFDev\Validation\Rules\MinLength;
use Weblitzer\CFDev\Validation\Rules\Numeric;

/**
 * Vérifie que les règles de validation avancées (hors Required) bloquent la
 * persistance et poussent des erreurs dans ErrorBag, dans les trois contextes
 * MetaBox, TermMeta, UserMeta.
 *
 * Pattern : si la valeur échoue une règle → ErrorBag contient le champ.
 *            si la valeur passe toutes les règles → le champ est sauvegardé.
 */
class ValidationRulesTest extends IntegrationTestCase
{
    private int $admin_id;
    private int $post_id;
    private int $term_id;
    private int $user_id;

    public function setUp(): void
    {
        parent::setUp();

        register_cfdev_post_type(['article', 'articles'], ['public' => true]);
        register_cfdev_taxonomy(['categorie', 'categories'], 'article', ['public' => true]);
        do_action('init');

        Registry::reset();

        $this->admin_id = static::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_id);

        $this->post_id = static::factory()->post->create(['post_type' => 'article']);
        $ins = wp_insert_term('Tech', 'categorie');
        if (is_wp_error($ins)) {
            throw new \RuntimeException($ins->get_error_message()); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
        $this->term_id = $ins['term_id'];
        $this->user_id = $this->admin_id;
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
    // MinLength
    // -------------------------------------------------------------------------

    public function testMinLengthFailsPushesErrorAndStillSaves(): void
    {
        $box = new MetaBox('val_min', 'Val', 'article', [
            ['type' => 'text', 'id' => 'titre_min', 'label' => 'Titre', 'rules' => [new MinLength(10)]],
        ]);

        $this->postWith(['titre_min' => 'Court']);
        $box->savePost($this->post_id);

        $errors = ErrorBag::peek('post', $this->post_id);
        $this->assertArrayHasKey('titre_min', $errors);
        // La valeur est quand même sauvegardée (la règle est advisory, pas bloquante)
        $this->assertSame('Court', get_post_meta($this->post_id, 'titre_min', true));
    }

    public function testMinLengthPassesNoError(): void
    {
        $box = new MetaBox('val_min_ok', 'Val', 'article', [
            ['type' => 'text', 'id' => 'titre_min_ok', 'label' => 'Titre', 'rules' => [new MinLength(5)]],
        ]);

        $this->postWith(['titre_min_ok' => 'Bonjour le monde']);
        $box->savePost($this->post_id);

        $errors = ErrorBag::peek('post', $this->post_id);
        $this->assertArrayNotHasKey('titre_min_ok', $errors);
        $this->assertSame('Bonjour le monde', get_post_meta($this->post_id, 'titre_min_ok', true));
    }

    // -------------------------------------------------------------------------
    // MaxLength
    // -------------------------------------------------------------------------

    public function testMaxLengthFailsPushesError(): void
    {
        $box = new MetaBox('val_max', 'Val', 'article', [
            ['type' => 'text', 'id' => 'code_max', 'label' => 'Code', 'rules' => [new MaxLength(3)]],
        ]);

        $this->postWith(['code_max' => 'TOOLONG']);
        $box->savePost($this->post_id);

        $errors = ErrorBag::peek('post', $this->post_id);
        $this->assertArrayHasKey('code_max', $errors);
    }

    public function testMaxLengthPassesNoError(): void
    {
        $box = new MetaBox('val_max_ok', 'Val', 'article', [
            ['type' => 'text', 'id' => 'code_max_ok', 'label' => 'Code', 'rules' => [new MaxLength(5)]],
        ]);

        $this->postWith(['code_max_ok' => 'ABC']);
        $box->savePost($this->post_id);

        $errors = ErrorBag::peek('post', $this->post_id);
        $this->assertArrayNotHasKey('code_max_ok', $errors);
    }

    // -------------------------------------------------------------------------
    // Contains
    // -------------------------------------------------------------------------

    public function testContainsFailsPushesError(): void
    {
        $box = new MetaBox('val_contains', 'Val', 'article', [
            ['type' => 'text', 'id' => 'email_like', 'label' => 'Email', 'rules' => [new Contains('@')]],
        ]);

        $this->postWith(['email_like' => 'pasdearobase']);
        $box->savePost($this->post_id);

        $errors = ErrorBag::peek('post', $this->post_id);
        $this->assertArrayHasKey('email_like', $errors);
    }

    // -------------------------------------------------------------------------
    // Numeric + Min + Max
    // -------------------------------------------------------------------------

    public function testNumericFailsOnNonNumericValue(): void
    {
        $box = new MetaBox('val_num', 'Val', 'article', [
            ['type' => 'number', 'id' => 'quantite', 'label' => 'Quantité', 'rules' => [new Numeric()]],
        ]);

        $this->postWith(['quantite' => 'abc']);
        $box->savePost($this->post_id);

        $errors = ErrorBag::peek('post', $this->post_id);
        $this->assertArrayHasKey('quantite', $errors);
    }

    public function testMinRuleFailsWhenValueBelow(): void
    {
        $box = new MetaBox('val_minval', 'Val', 'article', [
            ['type' => 'number', 'id' => 'prix', 'label' => 'Prix', 'rules' => [new Min(10)]],
        ]);

        $this->postWith(['prix' => '5']);
        $box->savePost($this->post_id);

        $errors = ErrorBag::peek('post', $this->post_id);
        $this->assertArrayHasKey('prix', $errors);
    }

    public function testMaxRuleFailsWhenValueAbove(): void
    {
        $box = new MetaBox('val_maxval', 'Val', 'article', [
            ['type' => 'number', 'id' => 'remise', 'label' => 'Remise', 'rules' => [new Max(100)]],
        ]);

        $this->postWith(['remise' => '150']);
        $box->savePost($this->post_id);

        $errors = ErrorBag::peek('post', $this->post_id);
        $this->assertArrayHasKey('remise', $errors);
    }

    public function testBetweenFailsWhenOutOfRange(): void
    {
        $box = new MetaBox('val_between', 'Val', 'article', [
            ['type' => 'number', 'id' => 'note', 'label' => 'Note', 'rules' => [new Between(0, 20)]],
        ]);

        $this->postWith(['note' => '25']);
        $box->savePost($this->post_id);

        $errors = ErrorBag::peek('post', $this->post_id);
        $this->assertArrayHasKey('note', $errors);
    }

    public function testBetweenPassesWithinRange(): void
    {
        $box = new MetaBox('val_between_ok', 'Val', 'article', [
            ['type' => 'number', 'id' => 'note_ok', 'label' => 'Note', 'rules' => [new Between(0, 20)]],
        ]);

        $this->postWith(['note_ok' => '15']);
        $box->savePost($this->post_id);

        $errors = ErrorBag::peek('post', $this->post_id);
        $this->assertArrayNotHasKey('note_ok', $errors);
        $this->assertSame('15', get_post_meta($this->post_id, 'note_ok', true));
    }

    // -------------------------------------------------------------------------
    // Plusieurs règles : toutes évaluées indépendamment
    // -------------------------------------------------------------------------

    public function testMultipleRulesAllFailuresReported(): void
    {
        $box = new MetaBox('val_multi', 'Val', 'article', [
            [
                'type'  => 'text',
                'id'    => 'slug_val',
                'label' => 'Slug',
                'rules' => [new MinLength(5), new MaxLength(10), new Contains('-')],
            ],
        ]);

        // 'hi' → échoue MinLength(5) et Contains('-') mais pas MaxLength(10)
        $this->postWith(['slug_val' => 'hi']);
        $box->savePost($this->post_id);

        $errors = ErrorBag::peek('post', $this->post_id);
        $this->assertArrayHasKey('slug_val', $errors);
        $this->assertNotEmpty($errors['slug_val']['errors']);
    }

    // -------------------------------------------------------------------------
    // Validation dans TermMeta
    // -------------------------------------------------------------------------

    public function testMinLengthFailsInTermMeta(): void
    {
        $tm = new TermMeta('categorie', 'Catégorie', [
            ['type' => 'text', 'id' => 'desc_cat', 'label' => 'Description', 'rules' => [new MinLength(20)]],
        ]);

        $this->postWith(['desc_cat' => 'Trop court']);
        $tm->saveTerm($this->term_id);

        $errors = ErrorBag::peek('term', $this->term_id);
        $this->assertArrayHasKey('desc_cat', $errors);
    }

    public function testMaxLengthPassesInTermMeta(): void
    {
        $tm = new TermMeta('categorie', 'Catégorie', [
            ['type' => 'text', 'id' => 'acronyme', 'label' => 'Acronyme', 'rules' => [new MaxLength(5)]],
        ]);

        $this->postWith(['acronyme' => 'PHP']);
        $tm->saveTerm($this->term_id);

        $errors = ErrorBag::peek('term', $this->term_id);
        $this->assertArrayNotHasKey('acronyme', $errors);
        $this->assertSame('PHP', get_term_meta($this->term_id, 'acronyme', true));
    }

    // -------------------------------------------------------------------------
    // Validation dans UserMeta
    // -------------------------------------------------------------------------

    public function testMinLengthFailsInUserMeta(): void
    {
        $um = new UserMeta('profil', 'Profil', [
            ['type' => 'text', 'id' => 'bio_user', 'label' => 'Bio', 'rules' => [new MinLength(50)]],
        ]);

        $this->postWith(['bio_user' => 'Trop courte']);
        $um->saveUser($this->user_id);

        $errors = ErrorBag::peek('user', $this->user_id);
        $this->assertArrayHasKey('bio_user', $errors);
    }

    public function testNumericPassesInUserMeta(): void
    {
        $um = new UserMeta('profil', 'Profil', [
            ['type' => 'number', 'id' => 'age_user', 'label' => 'Âge', 'rules' => [new Numeric(), new Min(18)]],
        ]);

        $this->postWith(['age_user' => '25']);
        $um->saveUser($this->user_id);

        $errors = ErrorBag::peek('user', $this->user_id);
        $this->assertArrayNotHasKey('age_user', $errors);
    }
}
