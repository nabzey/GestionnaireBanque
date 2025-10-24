<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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
        'metadata',
        'admin_id',
    ];

    protected $casts = [
        'solde_initial' => 'decimal:2',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

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

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    // Scopes locaux
    public function scopeNumero($query, $numero)
    {
        return $query->where('numero', $numero);
    }

    public function scopeClient($query, $telephone)
    {
        return $query->whereHas('admin', function ($q) use ($telephone) {
            $q->where('telephone', $telephone);
        });
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
                  ->orWhereHas('admin', function ($adminQuery) use ($search) {
                      $adminQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Tri
        $sortField = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';

        // Mapping des champs de tri
        $sortMapping = [
            'dateCreation' => 'created_at',
            'solde' => 'solde_initial',
            'titulaire' => 'admin.name',
        ];

        $actualSortField = $sortMapping[$sortField] ?? $sortField;

        if ($actualSortField === 'admin.name') {
            $query->join('admins', 'comptes.admin_id', '=', 'admins.id')
                  ->orderBy('admins.name', $sortOrder)
                  ->select('comptes.*');
        } else {
            $query->orderBy($actualSortField, $sortOrder);
        }

        return $query;
    }
}
