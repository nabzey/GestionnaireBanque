<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'type' => $this->type,
            'montant' => $this->montant,
            'montant_formate' => $this->montant_formate,
            'devise' => $this->devise,
            'description' => $this->description,
            'statut' => $this->statut,
            'date_execution' => $this->date_execution?->toISOString(),
            'date_creation' => $this->created_at->toISOString(),
            'derniere_modification' => $this->updated_at->toISOString(),
            'metadata' => $this->metadata,
            'compte' => $this->whenLoaded('compte', function () {
                return [
                    'id' => $this->compte->id,
                    'numero' => $this->compte->numero,
                    'type' => $this->compte->type,
                    'solde_initial' => $this->compte->solde_initial,
                    'devise' => $this->compte->devise,
                    'titulaire' => $this->compte->client ? $this->compte->client->nom_complet : null,
                ];
            }),
        ];
    }
}
