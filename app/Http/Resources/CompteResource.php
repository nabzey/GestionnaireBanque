<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'numeroCompte' => $this->numero,
            'titulaire' => $this->client ? $this->client->nom_complet : null,
            'type' => $this->type,
            'solde' => $this->solde,
            'devise' => $this->devise,
            'dateCreation' => $this->created_at->toISOString(),
            'statut' => $this->statut,
            'motifBlocage' => $this->motif_blocage,
            'source' => $this->source ?? 'local',
            'metadata' => $this->metadata,
        ];

        // Afficher les dates de blocage seulement pour les comptes épargne
        if ($this->type === 'epargne') {
            $data['dateDebutBlocage'] = $this->date_debut_blocage?->toISOString();
            $data['dateFinBlocage'] = $this->date_fin_blocage?->toISOString();
        }

        return $data;
    }
}
