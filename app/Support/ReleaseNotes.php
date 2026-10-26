<?php

namespace App\Support;

use App\Enums\PlatformRole;
use App\Models\User;

/**
 * Nouveautes presentees une seule fois a chaque utilisateur apres une
 * mise a jour. Pour annoncer une nouvelle version : ajouter une entree en
 * tete de RELEASES avec une version plus recente (format AAAA.MM.JJ).
 *
 * Chaque element peut cibler des roles ('roles' => ['admin']) ; sans cle
 * 'roles', il est montre a tout le monde.
 */
class ReleaseNotes
{
    private const RELEASES = [
        [
            'version' => '2026.10.26',
            'title' => 'Le menu de la semaine fait peau neuve',
            'intro' => 'Voici ce qui change pour vous depuis la derniere mise a jour.',
            'items' => [
                [
                    'icon' => 'ki-sun',
                    'paths' => 9,
                    'title' => 'Le menu du jour en tete de page',
                    'text' => 'Les plats du midi et du soir s\'affichent en premier, avec la periode en cours mise en avant. Le tableau de toute la semaine reste juste en dessous.',
                ],
                [
                    'icon' => 'ki-calendar',
                    'paths' => 2,
                    'title' => 'Un tableau fiable et lisible',
                    'text' => 'Les sept jours sont toujours alignes, un creneau sans plat est indique clairement, et sur telephone chaque jour a sa carte.',
                ],
                [
                    'icon' => 'ki-abstract-26',
                    'paths' => 2,
                    'title' => 'Cycle des menus',
                    'text' => 'Creez, copiez, renommez, reordonnez (fleches ou glisser-deposer), mettez en reserve ou supprimez les semaines depuis la nouvelle page « Cycle des menus ».',
                    'roles' => [PlatformRole::ADMIN],
                ],
                [
                    'icon' => 'ki-arrows-circle',
                    'paths' => 2,
                    'title' => 'Rotation automatique',
                    'text' => 'Activez l\'interrupteur et le cycle avance seul chaque lundi. Vous voyez a l\'avance quelle semaine passe a quelle date, et vous gardez la main a tout moment.',
                    'roles' => [PlatformRole::ADMIN],
                ],
                [
                    'icon' => 'ki-pencil',
                    'paths' => 2,
                    'title' => 'Plats modifies en une seule fois',
                    'text' => 'L\'editeur presente les sept jours sur un plateau : modifiez ce que vous voulez, masquez un creneau, puis enregistrez toute la semaine d\'un coup.',
                    'roles' => [PlatformRole::ADMIN],
                ],
                [
                    'icon' => 'ki-shield-tick',
                    'paths' => 2,
                    'title' => 'Confirmations harmonisees',
                    'text' => 'Toutes les actions sensibles passent par la meme fenetre de confirmation, y compris la suppression definitive d\'un membre, qui demande de saisir son nom.',
                    'roles' => [PlatformRole::ADMIN],
                ],
            ],
        ],
    ];

    /** Version annoncee actuellement. */
    public static function currentVersion(): string
    {
        return self::RELEASES[0]['version'];
    }

    /**
     * Nouveautes a montrer a cet utilisateur, ou null s'il les a deja vues.
     * Les elements reserves a d'autres roles sont retires.
     */
    public static function pendingFor(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        $seen = (string) ($user->whats_new_seen_version ?? '');

        if (version_compare($seen, self::currentVersion(), '>=')) {
            return null;
        }

        $release = self::RELEASES[0];
        $release['items'] = array_values(array_filter($release['items'], function ($item) use ($user) {
            return empty($item['roles']) || $user->hasAnyRole($item['roles']);
        }));

        return $release['items'] === [] ? null : $release;
    }
}
