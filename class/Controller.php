<?php
/**
 * This file is part of Docalist Duplicate.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Duplicate;

use Docalist\Duplicate\DuplicateApi;
use Docalist\Data\Database;
use Docalist\Data\Record;
use Docalist\AdminNotices;
use WP_Post;
use InvalidArgumentException;

/**
 * Contrôleur en charge de la duplication des notices docalist.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class Controller
{
    /**
     * Nom de l'action utilisée par le contrôleur.
     *
     * @var string
     */
    const ACTION = 'docalist-duplicate-post';

    /**
     * API utilisée.
     *
     * @var DuplicateApi
     */
    private $api;

    /**
     * Le service utilisé pour générer un message à l'utilisateur après la duplication.
     *
     * @var AdminNotices
     */
    private $adminNotices;

    /**
     * Constructeur
     *
     * @param DuplicateApi $api API à utiliser.
     */
    public function __construct(DuplicateApi $api, AdminNotices $adminNotices)
    {
        $this->api = $api;
        $this->adminNotices = $adminNotices;
    }

    /**
     * Retourne l'Api utilisée par le contrôleur.
     *
     * @return DuplicateApi
     */
    public function getApi(): DuplicateApi
    {
        return $this->api;
    }

    /**
     * Initialise les hooks utilisés.
     */
    public function initialize(): void
    {
        add_action('admin_action_' . self::ACTION, function () {
            $this->actionDuplicate($_REQUEST);
        });
    }

    /**
     * Retourne l'url à utiliser pour dupliquer le post fourni en paramètre.
     *
     * @param WP_Post $post
     *
     * @return string
     */
    public function getDuplicateUrl(WP_Post $post): string
    {
        $nonce = $this->api->createNonce($post);

        return admin_url(sprintf('admin.php?action=%s&post=%d&nonce=%s', self::ACTION, $post->ID, $nonce));
    }

    /**
     * Gère l'action admin "duplicate post".
     *
     * La méthode vérifie les paramètres fournis, appelle wp_die() s'il sont incorrects, duplique le post
     * indiqué puis redirige l'utilisateur vers l'écran d'édition du post obtenu.
     *
     * @param array $request Les paramètres fournis au contrôleur :
     *
     * - "post" : l'ID du post à dupliquer.
     * - "nonce" : le nonce généré par createNonce().
     */
    private function actionDuplicate(array $request): void
    {
        // Vérifie qu'on a un post
        $post = empty($request['post']) ? null : get_post((int) $request['post']);
        if (is_null($post)) {
            wp_die('bad post');
        }

        // Vérifie qu'on a un nonce et qu'il est correct
        $nonce = empty($request['nonce']) ? false : $this->api->verifyNonce($request['nonce'], $post);
        if (! $nonce) {
            wp_die('bad nonce');
        }

        // Duplique le post
        try {
            $newID = $this->api->duplicatePost($post);
        } catch (InvalidArgumentException $e) {
            wp_die($e->getMessage());
        }

        // Génère une admin notices indiquant à l'utilisateur que la notice a été dupliquée
        $title = __('Duplicata', 'docalist-duplicate');
        $notice = __(
            'Voici une copie de <i>%s</i> que vous pouvez modifier et enregistrer. Cette copie est actuellement
            en statut <i>brouillon auto</i> et sera supprimée automatiquement si vous ne faites aucune modification.',
            'docalist-duplicate'
        );
        $notice = sprintf($notice, get_the_title($post));
        $this->adminNotices->success($notice, $title);

        // Redirige l'utilisateur vers l'écran d'édition de la nouvelle notice
        wp_redirect(get_edit_post_link($newID, 'url'));
    }
}
