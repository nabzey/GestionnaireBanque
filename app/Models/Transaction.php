<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'reference',
        'type',
        'montant',
        'devise',
        'description',
        'statut',
        'date_execution',
        'metadata',
        'compte_id',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_execution' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->reference)) {
                $model->reference = static::generateReference();
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

    public static function generateReference()
    {
        do {
            $reference = 'TXN-' . strtoupper(Str::random(10));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }

    // Scopes locaux
    public function scopeValidee($query)
    {
        return $query->where('statut', 'validee');
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopePeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date_execution', [$debut, $fin]);
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

        if (isset($filters['compte_id']) && $filters['compte_id']) {
            $query->where('compte_id', $filters['compte_id']);
        }

        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Tri
        $sortField = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';

        // Mapping des champs de tri
        $sortMapping = [
            'dateExecution' => 'date_execution',
            'montant' => 'montant',
        ];

        $actualSortField = $sortMapping[$sortField] ?? $sortField;

        $query->orderBy($actualSortField, $sortOrder);

        return $query;
    }

    public function getMontantFormateAttribute()
    {
        return number_format($this->montant, 2, ',', ' ') . ' ' . $this->devise;
    }
}
