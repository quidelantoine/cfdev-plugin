<?php

namespace Weblitzer\CFDev\Tests\Integration\Cache;

use Weblitzer\CFDev\Admin\CachePage;
use Weblitzer\CFDev\Cache\CacheManager;
use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Meta\TermMeta;
use Weblitzer\CFDev\Meta\UserMeta;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie le cycle complet CacheManager : génération, lecture, invalidation.
 * Le cache est désactivé par défaut (OPTION_CACHE = false) → les données sont
 * toujours recalculées depuis la DB, ce qui permet de tester la logique sans
 * s'appuyer sur les fichiers .tmp.
 */
class CacheManagerTest extends IntegrationTestCase
{
    private int $admin_id;

    public function setUp(): void
    {
        parent::setUp();

        $this->admin_id = static::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_id);

        register_cfdev_post_type(['media', 'medias'], ['public' => true]);
        register_cfdev_taxonomy(['genre_media', 'genres media'], 'media', ['public' => true]);
        do_action('init');

        Registry::reset();
    }

    public function tearDown(): void
    {
        delete_option(CachePage::OPTION_CACHE);
        Registry::reset();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function manager(): CacheManager
    {
        return new CacheManager();
    }

    // -------------------------------------------------------------------------
    // post()
    // -------------------------------------------------------------------------

    public function testPostReturnsRegisteredFieldValues(): void
    {
        new MetaBox('media_details', 'Détails', 'media', [
            ['type' => 'text', 'id' => 'realisateur', 'label' => 'Réalisateur'],
            ['type' => 'text', 'id' => 'annee',       'label' => 'Année'],
        ]);

        $post_id = static::factory()->post->create(['post_type' => 'media']);
        update_post_meta($post_id, 'realisateur', 'Kubrick');
        update_post_meta($post_id, 'annee', '1980');

        $data = $this->manager()->post($post_id);

        $this->assertSame('Kubrick', $data['groups']['media_details']['realisateur']);
        $this->assertSame('1980', $data['groups']['media_details']['annee']);
    }

    public function testPostReturnsEmptyStringForUnsetField(): void
    {
        new MetaBox('media_extra', 'Extra', 'media', [
            ['type' => 'text', 'id' => 'synopsis', 'label' => 'Synopsis'],
        ]);

        $post_id = static::factory()->post->create(['post_type' => 'media']);

        $data = $this->manager()->post($post_id);

        $this->assertSame('', $data['groups']['media_extra']['synopsis']);
    }

    public function testPostIncludesPostIdAndGeneratedAt(): void
    {
        new MetaBox('media_base', 'Base', 'media', [
            ['type' => 'text', 'id' => 'titre_media', 'label' => 'Titre'],
        ]);

        $post_id = static::factory()->post->create(['post_type' => 'media']);
        $data    = $this->manager()->post($post_id);

        $this->assertSame($post_id, $data['post_id']);
        $this->assertArrayHasKey('generated_at', $data);
    }

    public function testPostOnlyReturnsGroupsMatchingPostType(): void
    {
        register_cfdev_post_type(['livre', 'livres'], ['public' => true]);
        do_action('init');

        new MetaBox('media_box', 'Media', 'media', [['type' => 'text', 'id' => 'champ_m', 'label' => 'M']]);
        new MetaBox('livres_box', 'Livres', 'livre', [['type' => 'text', 'id' => 'champ_l', 'label' => 'L']]);

        $media_id = static::factory()->post->create(['post_type' => 'media']);
        $data     = $this->manager()->post($media_id);

        $this->assertArrayHasKey('media_box', $data['groups']);
        $this->assertArrayNotHasKey('livres_box', $data['groups']);
    }

    // -------------------------------------------------------------------------
    // term()
    // -------------------------------------------------------------------------

    public function testTermReturnsRegisteredFieldValues(): void
    {
        new TermMeta('genre_media', 'Genre', [
            ['type' => 'text', 'id' => 'description_genre', 'label' => 'Description'],
        ]);

        $ins = wp_insert_term('Action', 'genre_media');
        if (is_wp_error($ins)) {
            $this->fail($ins->get_error_message());
        }
        $term_id = $ins['term_id'];
        update_term_meta($term_id, 'description_genre', 'Films d\'action');

        $data = $this->manager()->term($term_id, 'genre_media');

        $this->assertSame("Films d'action", $data['groups']['genre_media']['description_genre']);
    }

    public function testTermIncludesTermIdAndTaxonomy(): void
    {
        new TermMeta('genre_media', 'Genre', [
            ['type' => 'text', 'id' => 'sous_genre', 'label' => 'Sous-genre'],
        ]);

        $ins = wp_insert_term('Horreur', 'genre_media');
        if (is_wp_error($ins)) {
            $this->fail($ins->get_error_message());
        }
        $term_id = $ins['term_id'];
        $data    = $this->manager()->term($term_id, 'genre_media');

        $this->assertSame($term_id, $data['term_id']);
        $this->assertSame('genre_media', $data['taxonomy']);
    }

    // -------------------------------------------------------------------------
    // user()
    // -------------------------------------------------------------------------

    public function testUserReturnsRegisteredFieldValues(): void
    {
        new UserMeta('profil_cache', 'Profil', [
            ['type' => 'text', 'id' => 'poste_cache', 'label' => 'Poste'],
        ]);

        update_user_meta($this->admin_id, 'poste_cache', 'Développeur');

        $data = $this->manager()->user($this->admin_id);

        $this->assertSame('Développeur', $data['groups']['profil_cache']['poste_cache']);
    }

    // -------------------------------------------------------------------------
    // Invalidation
    // -------------------------------------------------------------------------

    public function testInvalidatePostDeletesCacheFile(): void
    {
        update_option(CachePage::OPTION_CACHE, '1');

        new MetaBox('cache_box', 'Cache', 'media', [
            ['type' => 'text', 'id' => 'champ_cache', 'label' => 'Champ'],
        ]);

        $post_id = static::factory()->post->create(['post_type' => 'media']);
        update_post_meta($post_id, 'champ_cache', 'valeur_initiale');

        $mgr = $this->manager();
        $mgr->post($post_id); // génère le cache

        // Invalide le cache
        $mgr->invalidatePost($post_id);

        // La prochaine lecture doit recalculer depuis la DB
        update_post_meta($post_id, 'champ_cache', 'valeur_mise_a_jour');
        $data = $mgr->post($post_id);

        $this->assertSame('valeur_mise_a_jour', $data['groups']['cache_box']['champ_cache']);
    }

    public function testSavePostHookInvalidatesCache(): void
    {
        update_option(CachePage::OPTION_CACHE, '1');

        new MetaBox('hook_box', 'Hook', 'media', [
            ['type' => 'text', 'id' => 'champ_hook', 'label' => 'Champ'],
        ]);

        $post_id = static::factory()->post->create(['post_type' => 'media']);
        update_post_meta($post_id, 'champ_hook', 'avant');

        // Force la génération du cache
        $mgr = $this->manager();
        $mgr->post($post_id);

        // Mise à jour de la méta + déclenchement du hook save_post
        update_post_meta($post_id, 'champ_hook', 'apres');
        do_action('save_post', $post_id);

        $data = $mgr->post($post_id);

        $this->assertSame('apres', $data['groups']['hook_box']['champ_hook']);
    }

    public function testEditedTermHookInvalidatesCache(): void
    {
        update_option(CachePage::OPTION_CACHE, '1');

        new TermMeta('genre_media', 'Genre', [
            ['type' => 'text', 'id' => 'note_genre', 'label' => 'Note'],
        ]);

        $ins = wp_insert_term('Thriller', 'genre_media');
        if (is_wp_error($ins)) {
            $this->fail($ins->get_error_message());
        }
        $term_id = $ins['term_id'];
        update_term_meta($term_id, 'note_genre', 'avant');

        $mgr = $this->manager();
        $mgr->term($term_id, 'genre_media');

        update_term_meta($term_id, 'note_genre', 'apres');
        do_action('edited_term', $term_id, 0, 'genre_media');

        $data = $mgr->term($term_id, 'genre_media');

        $this->assertSame('apres', $data['groups']['genre_media']['note_genre']);
    }

    public function testProfileUpdateHookInvalidatesUserCache(): void
    {
        update_option(CachePage::OPTION_CACHE, '1');

        new UserMeta('profil_hook', 'Profil', [
            ['type' => 'text', 'id' => 'ville_hook', 'label' => 'Ville'],
        ]);

        update_user_meta($this->admin_id, 'ville_hook', 'Paris');

        $mgr = $this->manager();
        $mgr->user($this->admin_id);

        update_user_meta($this->admin_id, 'ville_hook', 'Lyon');
        do_action('profile_update', $this->admin_id, get_userdata($this->admin_id));

        $data = $mgr->user($this->admin_id);

        $this->assertSame('Lyon', $data['groups']['profil_hook']['ville_hook']);
    }

    // -------------------------------------------------------------------------
    // inspect()
    // -------------------------------------------------------------------------

    public function testInspectReturnsCacheMetadata(): void
    {
        new MetaBox('inspect_box', 'Inspect', 'media', [
            ['type' => 'text', 'id' => 'champ_inspect', 'label' => 'Inspect'],
        ]);

        $post_id = static::factory()->post->create(['post_type' => 'media']);
        $result  = $this->manager()->inspect('post', $post_id);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('cache', $result);
        $this->assertArrayHasKey('enabled', $result['cache']);
        $this->assertArrayHasKey('hit', $result['cache']);
    }

    public function testInspectCacheDisabledByDefault(): void
    {
        new MetaBox('inspect_off', 'Inspect', 'media', [
            ['type' => 'text', 'id' => 'x_inspect', 'label' => 'X'],
        ]);

        $post_id = static::factory()->post->create(['post_type' => 'media']);
        $result  = $this->manager()->inspect('post', $post_id);

        $this->assertFalse($result['cache']['enabled']);
        $this->assertFalse($result['cache']['hit']);
    }
}
