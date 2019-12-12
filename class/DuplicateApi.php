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

use Docalist\Data\Database;
use Docalist\Data\Record;
use WP_Post;
use WP_Post_Type;
use InvalidArgumentException;

/**
 * API pour la duplication des notices docalist.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class DuplicateApi
{
    /**
     * Chaine utilisée pour générer le nonce dans les liens "dupliquer" générés (%d = post ID).
     *
     * @var string
     */
    const NONCE = 'docalist-duplicate-%d';

    /**
     * Liste des bases docalist pour lesquelles la duplication est activée.
     *
     * @var Database[] Un tableau de la forme "post-type" => Database.
     */
    private $databases;

    /**
     * Constructeur
     *
     * @param Database[] $databases Liste des bases docalist pour lesquelles la duplication est activée
     * (sous la forme d'un tableau de la forme "post-type" => Database).
     */
    public function __construct(array $databases)
    {
        $this->databases = $databases;
    }

    /**
     * Teste si le post passé en paramètre est duplicable.
     *
     * Le post est duplicable si les conditions suivantes sont remplies :
     *
     * - il s'agit d'une notice docalist,
     * - il n'est pas en statut "auto-draft",
     * - l'utilisateur dispose des droits requis.
     *
     * @param WP_Post $post
     *
     * @return bool
     */
    public function isDuplicable(WP_Post $post): bool
    {
        return $this->isDocalistPost($post)
            && ($post->post_status !== 'auto-draft')
            && $this->currentUserCanDuplicate($post);
    }

    /**
     * Teste si le post passé en paramètre est une notice docalist.
     *
     * @param WP_Post $post
     *
     * @return bool
     */
    private function isDocalistPost(WP_Post $post): bool
    {
        return isset($this->databases[$post->post_type]);
    }

    /**
     * Teste si l'utilisateur en cours dispose des droits requis pour dupliquer le post passé en paramètre.
     *
     * @param WP_Post $post
     *
     * @return bool
     */
    private function currentUserCanDuplicate(WP_Post $post): bool
    {
        $postType = get_post_type_object($post->post_type); /** @var WP_Post_Type $postType */

        return !is_null($postType) && current_user_can($postType->cap->create_posts);
    }

    /**
     * Retourne la chaine utilisée pour créer ou vérifier le nonce passé en paramètre dans les liens.
     *
     * @param WP_Post $post
     *
     * @return string
     */
    private function getNonceAction(WP_Post $post): string
    {
        return sprintf(self::NONCE, $post->ID);
    }

    /**
     * Crée le nonce à utiliser pour dupliquer le post passé en paramètre.
     *
     * @param WP_Post $post
     *
     * @return string
     */
    public function createNonce(WP_Post $post): string
    {
        return wp_create_nonce($this->getNonceAction($post));
    }

    /**
     * Vérifie le nonce fourni pour le post passé en paramètre.
     *
     * @param string $nonce
     *
     * @param WP_Post $post
     *
     * @return bool
     */
    public function verifyNonce(string $nonce, WP_Post $post): bool
    {
        return (bool) wp_verify_nonce($nonce, $this->getNonceAction($post)); // retourne false, 1 ou 2
    }

    /**
     * Duplique le post
     *
     * @param WP_Post $post Le post à dupliquer.
     *
     * @throws InvalidArgumentException Si le post n'est pas duplicable.
     *
     * @return int L'ID du post dupliqué.
     */
    public function duplicatePost(WP_Post $post): int
    {
        // Vérifie que le post est duplicable
        if (! $this->isDuplicable($post)) {
            throw new InvalidArgumentException(
                $this->isDocalistPost($post) ? 'Not allowed': 'Not a docalist post'
            );
        }

        // Récupère la base docalist qui contient ce post
        $database = $this->databases[$post->post_type]; // la clé existe, vérifié par isDuplicable()

        // Supprime l'ID du post pour qu'il soit traité comme un nouveau post
        $post->ID = null;

        // Crée une entité docalist à partir du post
        $record = $database->fromPost($post); /** @var Record $record */

        // Supprime les champs qu'on ne veut pas cloner (obtiendrons une valeur par défaut lors du save)
        unset($record->creation);
        unset($record->createdBy);
        unset($record->lastupdate);
        unset($record->ref);
        unset($record->slug);

        // La nouvelle notice est un "brouillon auto"
        $record->status = 'auto-draft';

        // Permet aux autres de modifier le clone ou de faire quelque chose avant l'enregistrement (pas d'ID)
        do_action('docalist_duplicate_before_save', $record);

        // Enregistre la nouvelle entité
        $database->save($record);

        // Permet aux autres de modifier le clone ou de faire quelque chose après l'enregistrement (on a un ID)
        do_action('docalist_duplicate_after_save', $record);

        // Retourne l'ID de la nouvelle entité
        return $record->getID();
    }
}
