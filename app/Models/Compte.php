<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Scopes\CompteScope;

class Compte extends Model
{
    use HasFactory, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'numero',
        'solde_initial',
        'devise',
        'type',
        'statut',
        'motif_blocage',
        'date_debut_blocage',
        'date_fin_blocage',
        'metadata',
        'client_id',
        'telephone',
    ];

    protected $casts = [
        'solde_initial' => 'decimal:2',
        'metadata' => 'array',
        'date_debut_blocage' => 'datetime',
        'date_fin_blocage' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        // Appliquer le scope global pour filtrer les comptes actifs
        static::addGlobalScope(new CompteScope);

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->numero)) {
                $model->numero = static::generateNumero();
            }
            if (empty($model->metadata)) {
                $model->metadata = [
                    'derniereModification' => now()->toISOString(),
                    'version' => 1
                ];
            }
        });

        static::updating(function ($model) {
            $model->metadata = array_merge($model->metadata ?? [], [
                'derniereModification' => now()->toISOString(),
                'version' => ($model->metadata['version'] ?? 1) + 1
            ]);
        });
    }

    public static function generateNumero()
    {
        do {
            $numero = 'CPT-' . strtoupper(Str::random(8));
        } while (self::where('numero', $numero)->exists());

        return $numero;
    }

    /**
     * Générer un code temporaire pour la première connexion
     */
    public static function generateCode()
    {
        return strtoupper(Str::random(6));
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Calculer le solde actuel basé sur les transactions
     */
    public function getSoldeAttribute(): float
    {
        // Solde initial + somme des dépôts - somme des retraits
        $depotTotal = $this->transactions()->where('type', 'depot')->sum('montant');
        $retraitTotal = $this->transactions()->where('type', 'retrait')->sum('montant');

        return $this->solde_initial + $depotTotal - $retraitTotal;
    }

    // Scopes locaux
    public function scopeNumero($query, $numero)
    {
        return $query->where('numero', $numero);
    }

    public function scopeClient($query, $telephone)
    {
        return $query->whereHas('client', function ($q) use ($telephone) {
            $q->where('telephone', $telephone);
        });
    }

    public function scopeByClientId($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope pour les comptes bloqués
     */
    public function scopeBlocked($query)
    {
        return $query->where('statut', 'bloque');
    }

    /**
     * Vérifier si le compte est bloqué
     */
    public function isBlocked(): bool
    {
        return $this->statut === 'bloque';
    }

    /**
     * Vérifier si le compte peut être bloqué (seulement épargne)
     */
    public function canBeBlocked(): bool
    {
        return $this->type === 'epargne' && $this->statut === 'actif';
    }

    /**
     * Vérifier si le compte peut être débloqué (dans les 2h suivant le blocage)
     */
    public function canBeUnblocked(): bool
    {
        if (!$this->isBlocked() || !$this->date_debut_blocage) {
            return false;
        }

        $twoHoursAfterBlock = $this->date_debut_blocage->copy()->addHours(2);
        return now()->lessThanOrEqualTo($twoHoursAfterBlock);
    }

    /**
     * Obtenir le temps restant avant expiration du délai de déblocage (en minutes)
     */
    public function getRemainingUnlockTime(): ?int
    {
        if (!$this->isBlocked() || !$this->date_debut_blocage) {
            return null;
        }

        $twoHoursAfterBlock = $this->date_debut_blocage->copy()->addHours(2);
        $remaining = now()->diffInMinutes($twoHoursAfterBlock, false);

        return max(0, $remaining);
    }

    // Scope pour la pagination et les filtres
    public function scopeFilterAndSort($query, array $filters = [])
    {
        // Filtres
        if (isset($filters['type']) && $filters['type']) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['statut']) && $filters['statut']) {
            $query->where('statut', $filters['statut']);
        }

        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($clientQuery) use ($search) {
                      $clientQuery->where('nom', 'like', "%{$search}%")
                                  ->orWhere('prenom', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Tri
        $sortField = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';

        // Mapping des champs de tri
        $sortMapping = [
            'numeroCompte' => 'numero',
            'dateCreation' => 'created_at',
            'solde' => 'solde_initial',
            'titulaire' => 'clients.nom',
        ];

        $actualSortField = $sortMapping[$sortField] ?? $sortField;

        if ($actualSortField === 'clients.nom') {
            $query->join('clients', 'comptes.client_id', '=', 'clients.id')
                  ->orderBy('clients.nom', $sortOrder)
                  ->select('comptes.*');
        } else {
            $query->orderBy($actualSortField, $sortOrder);
        }

        return $query;
    }
}
