<?php

namespace Weblitzer\CFDev\Tests\Integration\Meta;

use ReflectionProperty;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;
use Weblitzer\CFDev\Validation\ErrorBag;

/**
 * Vérifie le cycle complet push → peek → load → forField de l'ErrorBag.
 * Tous les appels utilisent des vrais WP transients (base de données réelle).
 */
class ErrorBagTransientTest extends IntegrationTestCase
{
    private static ReflectionProperty $runtimeProp;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $prop = new ReflectionProperty(ErrorBag::class, 'runtime');
        $prop->setAccessible(true);
        self::$runtimeProp = $prop;
    }

    public function setUp(): void
    {
        parent::setUp();
        self::$runtimeProp->setValue(null, null);
    }

    // -------------------------------------------------------------------------
    // Push / Peek
    // -------------------------------------------------------------------------

    public function testPushStoresErrorsInTransient(): void
    {
        $errors = ['titre' => ['label' => 'Titre', 'errors' => ['Champ requis.']]];

        ErrorBag::push('post', 1, $errors);

        $result = ErrorBag::peek('post', 1);

        $this->assertSame($errors, $result);
    }

    public function testPushMergesMultipleCalls(): void
    {
        ErrorBag::push('post', 2, ['champ_a' => ['label' => 'A', 'errors' => ['err1']]]);
        ErrorBag::push('post', 2, ['champ_b' => ['label' => 'B', 'errors' => ['err2']]]);

        $result = ErrorBag::peek('post', 2);

        $this->assertArrayHasKey('champ_a', $result);
        $this->assertArrayHasKey('champ_b', $result);
    }

    public function testPeekDoesNotDeleteTransient(): void
    {
        $errors = ['x' => ['label' => 'X', 'errors' => ['fail']]];

        ErrorBag::push('post', 3, $errors);
        ErrorBag::peek('post', 3);
        $second = ErrorBag::peek('post', 3);

        $this->assertSame($errors, $second);
    }

    public function testPeekReturnsEmptyArrayWhenNoErrors(): void
    {
        $result = ErrorBag::peek('post', 9999);

        $this->assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // Load → forField / all
    // -------------------------------------------------------------------------

    public function testLoadMovesErrorsToRuntimeAndDeletesTransient(): void
    {
        $errors = ['email' => ['label' => 'Email', 'errors' => ['Format invalide.']]];
        ErrorBag::push('post', 4, $errors);

        ErrorBag::load('post', 4);

        // Transient supprimé
        $this->assertSame([], ErrorBag::peek('post', 4));
        // Runtime chargé
        $this->assertSame(['Format invalide.'], ErrorBag::forField('email'));
    }

    public function testLoadIsIdempotent(): void
    {
        $errors = ['nom' => ['label' => 'Nom', 'errors' => ['Trop court.']]];
        ErrorBag::push('post', 5, $errors);

        ErrorBag::load('post', 5);
        // Deuxième appel ne doit pas effacer le runtime
        ErrorBag::load('post', 5);

        $this->assertSame(['Trop court.'], ErrorBag::forField('nom'));
    }

    public function testForFieldReturnsEmptyWhenNoErrorForKey(): void
    {
        ErrorBag::push('post', 6, ['x' => ['label' => 'X', 'errors' => ['err']]]);
        ErrorBag::load('post', 6);

        $this->assertSame([], ErrorBag::forField('inexistant'));
    }

    public function testAllReturnsFullRuntime(): void
    {
        $errors = [
            'a' => ['label' => 'A', 'errors' => ['err_a']],
            'b' => ['label' => 'B', 'errors' => ['err_b']],
        ];
        ErrorBag::push('post', 7, $errors);
        ErrorBag::load('post', 7);

        $this->assertSame($errors, ErrorBag::all());
    }

    // -------------------------------------------------------------------------
    // Isolation par meta_type et object_id
    // -------------------------------------------------------------------------

    public function testErrorsAreIsolatedByObjectId(): void
    {
        ErrorBag::push('post', 10, ['f' => ['label' => 'F', 'errors' => ['err_10']]]);
        ErrorBag::push('post', 11, ['f' => ['label' => 'F', 'errors' => ['err_11']]]);

        $this->assertSame([['label' => 'F', 'errors' => ['err_10']]], array_values(ErrorBag::peek('post', 10)));
        $this->assertSame([['label' => 'F', 'errors' => ['err_11']]], array_values(ErrorBag::peek('post', 11)));
    }

    public function testErrorsAreIsolatedByMetaType(): void
    {
        ErrorBag::push('post', 20, ['f' => ['label' => 'F', 'errors' => ['post_err']]]);
        ErrorBag::push('term', 20, ['f' => ['label' => 'F', 'errors' => ['term_err']]]);

        $this->assertSame('post_err', ErrorBag::peek('post', 20)['f']['errors'][0]);
        $this->assertSame('term_err', ErrorBag::peek('term', 20)['f']['errors'][0]);
    }
}
