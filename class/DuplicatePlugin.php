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

use Docalist\Data\Plugin as DocalistData;
use Docalist\AdminNotices;
use Docalist\Duplicate\DuplicateApi;
use Docalist\Duplicate\Controller;
use Docalist\Duplicate\PostRowActions;
use Docalist\Duplicate\AdminBarLink;
use Docalist\Duplicate\Metabox;

/**
 * Plugin docalist-duplicate.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class DuplicatePlugin
{
    /**
     * Initialise le plugin.
     *
     * @param DocalistData  $docalistData   Le plugin docalist-data à utiliser.
     * @param AdminNotices  $adminNotices   Le service à utiliser pour afficher un message
     *                                      à l'utilisateur après la duplication.
     */
    public function initialize(DocalistData $docalistData, AdminNotices $adminNotices): void
    {
        // Récupère la liste des bases de données docalist
        if (empty($databases = $docalistData->databases())) {
            return;
        }

        // Charge les fichiers de traduction du plugin
        load_plugin_textdomain('docalist-duplicate', false, 'docalist-duplicate/languages');

        // Initialise l'Api
        $api = new DuplicateApi($databases);

        // Initialise le contrôleur
        $controller = new Controller($api, $adminNotices);
        $controller->initialize();

        // Crée des liens "dupliquer" dans les listes de notices
        $postRowActions = new PostRowActions($controller);
        $postRowActions->initialize();

        // Ajoute un lien "dupliquer" dans la barre d'outils de WordPress
        $adminBarLink = new AdminBarLink($controller);
        $adminBarLink->initialize();

        // Ajoute une metabox "dupliquer" dans la page d'édition des notices
        $metabox = new Metabox($controller);
        $metabox->initialize();
    }
}
