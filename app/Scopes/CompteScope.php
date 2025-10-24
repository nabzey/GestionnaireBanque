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
        // Les comptes non supprimés sont automatiquement gérés par SoftDeletes
        // Ce scope pourrait être étendu pour d'autres filtres globaux si nécessaire
    }
}