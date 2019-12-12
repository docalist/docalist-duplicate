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

use Docalist\Duplicate\Controller;
use WP_Post;
use WP_Admin_Bar;

/**
 * Ajoute un lien "dupliquer" dans la barre d'outils de WordPress.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class AdminBarLink
{
    /**
     * Contrôleur
     *
     * @var Controller
     */
    private $controller;

    /**
     * Constructeur
     */

    public function __construct(Controller $controller)
    {
        $this->controller = $controller;
    }

    /**
     * Initialise les hooks utilisés.
     */
    public function initialize(): void
    {
        add_filter(
            'admin_bar_menu',
            function (WP_Admin_Bar $adminBar): void {
                $post = is_admin() ? $this->getPostInAdmin() : $this->getPostOnFront();
                if (is_null($post)) {
                    return;
                }

                if ($this->controller->getApi()->isDuplicable($post)) {
                    $url = $this->controller->getDuplicateUrl($post);
                    $title = __('Crée une copie de cette notice Docalist', 'docalist-duplicate');
                    $text = sprintf(
                        '<span class="ab-icon dashicon-before dashicons-images-alt"></span>%s',
                        __('Dupliquer', 'docalist-duplicate')
                    );

                    $adminBar->add_node([
                        'id'    => 'docalist-duplicate',
                        'title' => $text,
                        'href'  => $url,
                        'meta'  => ['title' => $title]
                    ]);
                }
            },
            81 // Juste après le menu "créer" (priorité 80), cf. WP_Admin_Bar::add_menus()
        );
    }

    private function getPostInAdmin(): ?WP_Post
    {
        return isset($_GET['post']) ? get_post((int) $_GET['post']) : null;
    }

    private function getPostOnFront(): ?WP_Post
    {
        return is_single() ? get_post() : null;
    }
}
