<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'nom_complet' => $this->nom_complet,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'date_naissance' => $this->date_naissance?->toISOString(),
            'adresse' => $this->adresse,
            'ville' => $this->ville,
            'pays' => $this->pays,
            'statut' => $this->statut,
            'date_creation' => $this->created_at->toISOString(),
            'derniere_modification' => $this->updated_at->toISOString(),
            'metadata' => $this->metadata,
            'comptes_count' => $this->whenLoaded('comptes', function () {
                return $this->comptes->count();
            }),
            'comptes' => $this->whenLoaded('comptes', function () {
                return CompteResource::collection($this->comptes);
            }),
        ];
    }
}
