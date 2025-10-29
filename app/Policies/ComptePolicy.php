<?php

namespace App\Policies;

use App\Models\Compte;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ComptePolicy
{
    /**
     * Déterminer si l'utilisateur peut voir tous les comptes
     */
    public function viewAny(User $user): bool
    {
        // Admin peut voir tous les comptes
        return $user->isAdmin();
    }

    /**
     * Déterminer si l'utilisateur peut voir un compte spécifique
     */
    public function view(User $user, Compte $compte): bool
    {
        // Admin peut voir tous les comptes
        if ($user->isAdmin()) {
            return true;
        }

        // Client ne peut voir que ses propres comptes
        return $user->isClient() && $compte->client_id === $user->client->id;
    }

    /**
     * Déterminer si l'utilisateur peut créer un compte
     */
    public function create(User $user): bool
    {
        // Seuls les admins peuvent créer des comptes
        return $user->isAdmin();
    }

    /**
     * Déterminer si l'utilisateur peut modifier un compte
     */
    public function update(User $user, Compte $compte): bool
    {
        // Admin peut modifier tous les comptes
        if ($user->isAdmin()) {
            return true;
        }

        // Client peut modifier ses propres comptes (sauf blocage/déblocage)
        if ($user->isClient() && $compte->client_id === $user->client->id) {
            // Client ne peut pas modifier un compte bloqué
            return $compte->statut !== 'bloque';
        }

        return false;
    }

    /**
     * Déterminer si l'utilisateur peut bloquer un compte
     */
    public function block(User $user, Compte $compte): bool
    {
        // Seuls les admins peuvent bloquer des comptes
        // Et seulement les comptes épargne actifs
        return $user->isAdmin() &&
               $compte->type === 'epargne' &&
               $compte->statut === 'actif';
    }

    /**
     * Déterminer si l'utilisateur peut débloquer un compte
     */
    public function unblock(User $user, Compte $compte): bool
    {
        // Seuls les admins peuvent débloquer des comptes
        // Et seulement les comptes bloqués dans les 2h
        return $user->isAdmin() &&
               $compte->statut === 'bloque' &&
               $compte->canBeUnblocked();
    }

    /**
     * Déterminer si l'utilisateur peut fermer un compte
     */
    public function close(User $user, Compte $compte): bool
    {
        // Seuls les admins peuvent fermer des comptes
        // Et seulement les comptes non bloqués
        return $user->isAdmin() && $compte->statut !== 'bloque';
    }

    /**
     * Déterminer si l'utilisateur peut supprimer un compte
     */
    public function delete(User $user, Compte $compte): bool
    {
        // Seuls les admins peuvent supprimer des comptes
        // Et seulement les comptes actifs (pas bloqués)
        return $user->isAdmin() && $compte->statut === 'actif';
    }

    /**
     * Déterminer si l'utilisateur peut voir les comptes archivés
     */
    public function viewArchived(User $user): bool
    {
        // Seuls les admins peuvent voir les comptes archivés
        return $user->isAdmin();
    }

    /**
     * Déterminer si l'utilisateur peut voir les comptes dans Neon
     */
    public function viewNeon(User $user): bool
    {
        // Seuls les admins peuvent voir les comptes dans Neon
        return $user->isAdmin();
    }
}