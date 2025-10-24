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
        return [
            'id' => $this->id,
            'numeroCompte' => $this->numero,
            'titulaire' => $this->admin->name ?? null,
            'type' => $this->type,
            'solde' => $this->solde_initial,
            'devise' => $this->devise,
            'dateCreation' => $this->created_at->toISOString(),
            'statut' => $this->statut,
            'motifBlocage' => $this->motif_blocage,
            'metadata' => $this->metadata,
        ];
    }
}
