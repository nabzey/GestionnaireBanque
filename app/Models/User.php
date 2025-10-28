<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'userable_type',
        'userable_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Relation polymorphique vers Admin ou Client
     */
    public function userable()
    {
        return $this->morphTo();
    }

    /**
     * Vérifier si l'utilisateur est un admin
     */
    public function isAdmin(): bool
    {
        return $this->userable_type === 'admin';
    }

    /**
     * Vérifier si l'utilisateur est un client
     */
    public function isClient(): bool
    {
        return $this->userable_type === 'client';
    }

    /**
     * Obtenir l'admin associé (si applicable)
     */
    public function admin()
    {
        if ($this->isAdmin()) {
            // Retourner un tableau au lieu d'un objet pour éviter l'erreur de relation
            return [
                'id' => $this->userable_id,
                'name' => $this->name,
                'email' => $this->email,
            ];
        }
        return null;
    }

    /**
     * Obtenir le client associé (si applicable)
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'userable_id');
    }

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $role): bool
    {
        return match($role) {
            'admin' => $this->isAdmin(),
            'client' => $this->isClient(),
            default => false,
        };
    }

    /**
     * Authentifier un utilisateur et générer un token
     */
    public static function authenticate(array $credentials): ?array
    {
        $user = self::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        // Créer le token Passport
        $token = $user->createToken('API Token')->accessToken;

        // Déterminer le rôle
        $role = $user->isAdmin() ? 'admin' : 'client';

        // Récupérer les informations du profil
        $profile = null;
        if ($user->isAdmin()) {
            $profile = [
                'id' => $user->userable_id,
                'name' => $user->name,
                'email' => $user->email,
            ];
        } elseif ($user->isClient()) {
            $profile = $user->client;
        }

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'profile' => $profile,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Créer un nouveau client avec son compte utilisateur
     */
    public static function registerClient(array $data): array
    {
        // Générer un mot de passe temporaire
        $temporaryPassword = self::generateTemporaryPassword();

        // Créer le client
        $client = \App\Models\Client::create([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'telephone' => $data['telephone'] ?? null,
            'nci' => $data['nci'] ?? null,
        ]);

        // Créer l'utilisateur
        $user = self::create([
            'name' => $data['prenom'] . ' ' . $data['nom'],
            'email' => $data['email'],
            'password' => Hash::make($temporaryPassword),
            'email_verified_at' => now(),
            'userable_type' => 'client',
            'userable_id' => $client->id,
        ]);

        // Créer le token
        $token = $user->createToken('API Token')->accessToken;

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'client',
                'profile' => $client,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
            'temporary_password' => $temporaryPassword,
        ];
    }

    /**
     * Générer un mot de passe temporaire
     */
    public static function generateTemporaryPassword(): string
    {
        return \Illuminate\Support\Str::random(8);
    }

    /**
     * Révoquer le token actuel de l'utilisateur
     */
    public function revokeCurrentToken(): bool
    {
        $this->token()->revoke();
        return true;
    }

    /**
     * Obtenir les informations complètes de l'utilisateur
     */
    public function getFullInfo(): array
    {
        $role = $this->isAdmin() ? 'admin' : 'client';

        $profile = null;
        if ($this->isAdmin()) {
            $profile = [
                'id' => $this->userable_id,
                'name' => $this->name,
                'email' => $this->email,
            ];
        } elseif ($this->isClient()) {
            $profile = $this->client;
        }

        return [
            'user' => [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'role' => $role,
                'profile' => $profile,
            ],
        ];
    }
}
