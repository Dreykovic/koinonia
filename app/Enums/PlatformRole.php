<?php

namespace App\Enums;

class PlatformRole
{
    public const ADMIN = 'admin';
    public const COMMUNICATION = 'communication';
    public const MEMBRE = 'membre';
    public const AUTRE = 'autre';

    /**
     * Liste officielle des rôles plateforme
     */
    public static function all(): array
    {
        return [
            self::ADMIN,
            self::COMMUNICATION,
            self::MEMBRE,
            self::AUTRE,
        ];
    }

    /**
     * Mappage des rôles vers leurs labels human-readable
     */
    public static function labels(): array
    {
        return [
            self::ADMIN => 'Administrateur',
            self::COMMUNICATION => 'Communication',
            self::MEMBRE => 'Membre',
            self::AUTRE => 'Autre',
        ];
    }

    /**
     * Obtenir le label d'un rôle spécifique
     */
    public static function label(string $role): string
    {
        return self::labels()[$role] ?? ucfirst($role);
    }

    /**
     * Vérifier si un rôle existe
     */
    public static function exists(string $role): bool
    {
        return in_array($role, self::all());
    }

    /**
     * Obtenir tous les rôles sous forme de tableau [value => label]
     * Utile pour les select/dropdown
     */
    public static function options(): array
    {
        return self::labels();
    }
}
