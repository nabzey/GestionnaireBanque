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
        // Filtrer uniquement les comptes actifs et non supprimés
        $builder->where('statut', 'actif')
                ->whereIn('type', ['cheque', 'epargne']);
    }
}