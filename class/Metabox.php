<?php declare(strict_types=1);
/**
 * This file is part of Docalist Duplicate.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Duplicate;

use Docalist\Duplicate\Controller;
use WP_Post;

/**
 * Ajoute une metabox "dupliquer" dans la page d'édition des notices.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class Metabox
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
            'add_meta_boxes',
            function (string $postType, WP_Post $post): void {
                if (! $this->controller->getApi()->isDuplicable($post)) {
                    return;
                }

                $id = 'docalist-duplicate';
                $title = __('Dupliquer', 'docalist-duplicate');
                $context = 'side';
                $priority = 'default';
                $callback = function () use ($post) {
                    $this->displayMetaboxContent($post);
                };

                add_meta_box($id, $title, $callback, null, $context, $priority);
            },
            10,
            2
        );
    }

    private function displayMetaBoxContent(WP_Post $post): void
    {
        printf(
            '<p>%s</p><p style="text-align:right"><a class="button" href="%s">%s</a></p>',
            __('Utilisez le bouton ci-dessous pour créer une notice similaire.', 'docalist-duplicate'),
            esc_attr($this->controller->getDuplicateUrl($post)),
            __('Dupliquer la notice', 'docalist-duplicate')
        );
    }
}
