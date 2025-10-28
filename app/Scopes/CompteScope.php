<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompteScope implements Scope
{
    /**
     * Appliquer le scope global aux comptes non supprimés
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Appliquer le filtre global pour comptes non supprimés uniquement
        // Le filtrage par statut sera fait au niveau du contrôleur selon le rôle utilisateur
        $builder->whereNull('deleted_at');
    }
}