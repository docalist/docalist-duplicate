<?php declare(strict_types=1);
/**
 * This file is part of Docalist Duplicate.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * Plugin Name: Docalist Duplicate
 * Plugin URI:  https://docalist.org/
 * Description: Duplication de notices Docalist.
 * Version:     1.2.0-dev
 * Author:      Daniel Ménard
 * Author URI:  http://docalist.org/
 * Text Domain: docalist-duplicate
 * Domain Path: /languages
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Duplicate;

use Docalist\Duplicate\DuplicatePlugin;
use Docalist\Data\Plugin as DocalistData;
use Docalist\AdminNotices;

/**
 * Version du plugin.
 */
define('DOCALIST_DUPLICATE_VERSION', '1.2.0-dev'); // Garder synchro avec la version indiquée dans l'entête

/**
 * Path absolu du répertoire dans lequel le plugin est installé.
 *
 * Par défaut, on utilise la constante magique __DIR__ qui retourne le path réel du répertoire et résoud les liens
 * symboliques.
 *
 * Si le répertoire du plugin est un lien symbolique, la constante doit être définie manuellement dans le fichier
 * wp_config.php et pointer sur le lien symbolique et non sur le répertoire réel.
 */
!defined('DOCALIST_DUPLICATE_DIR') && define('DOCALIST_DUPLICATE_DIR', __DIR__);

/**
 * Path absolu du fichier principal du plugin.
 */
define('DOCALIST_DUPLICATE', DOCALIST_DUPLICATE_DIR . DIRECTORY_SEPARATOR . basename(__FILE__));

/**
 * Url de base du plugin.
 */
define('DOCALIST_DUPLICATE_URL', plugins_url('', DOCALIST_DUPLICATE));

/**
 * Initialise le plugin.
 */
add_action('plugins_loaded', function () {
    // Auto désactivation si les plugins dont on a besoin ne sont pas activés
    $dependencies = ['DOCALIST_CORE', 'DOCALIST_DATA'];
    foreach ($dependencies as $dependency) {
        if (! defined($dependency)) {
            return add_action('admin_notices', function () use ($dependency) {
                deactivate_plugins(DOCALIST_DUPLICATE);
                unset($_GET['activate']); // empêche wp d'afficher "extension activée"
                printf(
                    '<div class="%s"><p><b>%s</b> has been deactivated because it requires <b>%s</b>.</p></div>',
                    'notice notice-error is-dismissible',
                    'Docalist Duplicate',
                    ucwords(strtolower(strtr($dependency, '_', ' ')))
                );
            });
        }
    }

    // Déclare nos classes dans l'autoloader Docalist
    docalist('autoloader')
        ->add(__NAMESPACE__, __DIR__ . '/class')
        ->add(__NAMESPACE__ . '\Tests', __DIR__ . '/tests');

    // Initialise le plugin
    add_action('init', function () {
        $duplicatePlugin = new DuplicatePlugin();
        $docalistData = docalist('docalist-data'); /** @var DocalistData $docalistData */
        $adminNotices = docalist('admin-notices'); /** @var AdminNotices $adminNotices */
        $duplicatePlugin->initialize($docalistData, $adminNotices);
    });
});
