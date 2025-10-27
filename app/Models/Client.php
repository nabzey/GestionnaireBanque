<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'nci',
        'date_naissance',
        'adresse',
        'ville',
        'pays',
        'statut',
        'metadata',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
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

    public function comptes()
    {
        return $this->hasMany(Compte::class);
    }

    // Scopes locaux
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeTelephone($query, $telephone)
    {
        return $query->where('telephone', $telephone);
    }

    public function scopeEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    // Scope pour la pagination et les filtres
    public function scopeFilterAndSort($query, array $filters = [])
    {
        // Filtres
        if (isset($filters['statut']) && $filters['statut']) {
            $query->where('statut', $filters['statut']);
        }

        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        // Tri
        $sortField = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';

        // Mapping des champs de tri
        $sortMapping = [
            'nomComplet' => 'nom',
            'dateCreation' => 'created_at',
        ];

        $actualSortField = $sortMapping[$sortField] ?? $sortField;

        $query->orderBy($actualSortField, $sortOrder);

        return $query;
    }

    public function getNomCompletAttribute()
    {
        return $this->prenom . ' ' . $this->nom;
    }
}
