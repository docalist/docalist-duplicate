<?php declare(strict_types=1);
/**
 * This file is part of Docalist Duplicate.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Duplicate;

use Docalist\Duplicate\Controller;
use WP_Post;

/**
 * Ajoute un lien "dupliquer" pour chaque notice affichée dans la liste des notices.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class PostRowActions
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
        add_filter('post_row_actions', function (array $actions, WP_Post $post): array {
            if ($this->controller->getApi()->isDuplicable($post)) {
                $url = $this->controller->getDuplicateUrl($post);
                $title = __('Crée une copie de cette notice Docalist', 'docalist-duplicate');
                $text = __('Dupliquer', 'docalist-duplicate');
                $link = sprintf('<a href="%s" aria-label="%s">%s</a>', esc_attr($url), esc_attr($title), $text);
                $actions['docalist-duplicate'] = $link;
            }

            return $actions;
        }, 10, 2);
    }
}
