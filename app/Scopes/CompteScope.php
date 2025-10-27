<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompteScope implements Scope
{
    /**
     * Appliquer le scope global aux comptes actifs chèque/épargne
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Filtrer uniquement les comptes actifs de type chèque ou épargne
        $builder->where('statut', 'actif')
                ->whereIn('type', ['cheque', 'epargne']);
    }
}