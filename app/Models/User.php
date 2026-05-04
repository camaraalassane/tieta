<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\VerifyEmailNotification;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $prenom     
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property mixed $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Concour> $concoursGeres
 * @property-read int|null $concours_geres_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutRole($roles, $guard = null)
 * @mixin \Eloquent
 */
class User extends Authenticatable 
{
    use HasRoles;
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'prenom',
        'email',
        'password',
        'email_verified_at', // ⭐ AJOUTER CETTE LIGNE SI ELLE N'EXISTE PAS
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

    public function getCreatedAtAttribute()
    {
        return date('d-m-Y H:i', strtotime($this->attributes['created_at']));
    }

    public function getUpdatedAtAttribute()
    {
        return date('d-m-Y H:i', strtotime($this->attributes['updated_at']));
    }


    public function getPermissionArray()
    {
        return $this->getAllPermissions()->pluck('name')->toArray();
    }

    // Gerer les concours par des admin
    public function concoursGeres()
    {
        return $this->belongsToMany(Concour::class, 'concour_admins');
    }

    // Un user a un profil
    public function profil()
    {
        return $this->hasOne(Profil::class);
    }

    public function candidatures()
    {
        return $this->hasManyThrough(
            Candidature::class,
            Profil::class,
            'user_id',
            'profil_id',
            'id',
            'id'
        );
    }

    // ⭐ NOUVELLES MÉTHODES POUR L'ARCHITECTURE DES SERVICES

    // Relation avec le service dont l'utilisateur est gérant principal
    public function serviceGerant()
    {
        return $this->hasOne(Service::class, 'gerant_id');
    }

    // Relation avec le service via la table service_users (personnel)
    public function service()
    {
        return $this->belongsToMany(Service::class, 'service_users', 'user_id', 'service_id')
            ->withPivot('role_in_service', 'is_active')
            ->withTimestamps();
    }

    // Relation avec les services où l'utilisateur est admin (via service_admins - à déprécier)
    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_admins', 'user_id', 'service_id');
    }

    // ⭐ Obtenir le service de l'utilisateur (la méthode principale)
    public function getService()
    {
        return $this->service()->first();
    }

    // ⭐ Obtenir le rôle de l'utilisateur dans son service
    public function getRoleInService()
    {
        $serviceUser = $this->service()->first();
        if ($serviceUser) {
            return $serviceUser->pivot->role_in_service;
        }
        return null;
    }

    // ⭐ Vérifier si l'utilisateur est gérant de son service
    public function isGerantOfService()
    {
        $serviceUser = $this->service()->first();
        return $serviceUser && $serviceUser->pivot->role_in_service === 'gerant';
    }

    // ⭐ Vérifier si l'utilisateur est admin de son service
    public function isAdminOfService()
    {
        $serviceUser = $this->service()->first();
        return $serviceUser && $serviceUser->pivot->role_in_service === 'admin';
    }

    // ⭐ Vérifier si l'utilisateur est gérant (rôle global)
    public function isGerant()
    {
        return $this->hasRole('gerant');
    }

    // ⭐ Vérifier si l'utilisateur a accès à un service spécifique
    public function hasAccessToService($serviceId)
    {
        if ($this->hasRole('superadmin')) return true;

        // Vérifier si l'utilisateur appartient à ce service
        $serviceUser = $this->service()->where('service_id', $serviceId)->first();
        return $serviceUser !== null;
    }

    // ⭐ Vérifier si l'utilisateur peut gérer un service (superadmin ou gérant du service)
    public function canManageService($serviceId)
    {
        if ($this->hasRole('superadmin')) return true;

        $serviceUser = $this->service()->where('service_id', $serviceId)->first();
        return $serviceUser && $serviceUser->pivot->role_in_service === 'gerant';
    }

    // ⭐ Vérifier si l'utilisateur peut gérer le personnel d'un service
    public function canManagePersonnel($serviceId)
    {
        if ($this->hasRole('superadmin')) return true;

        $serviceUser = $this->service()->where('service_id', $serviceId)->first();
        return $serviceUser && in_array($serviceUser->pivot->role_in_service, ['gerant', 'admin']);
    }

    // ⭐ Obtenir l'ID du service de l'utilisateur
    public function getServiceId()
    {
        $service = $this->getService();
        return $service ? $service->id : null;
    }

    // ⭐ Obtenir le nom du service de l'utilisateur
    public function getServiceName()
    {
        $service = $this->getService();
        return $service ? $service->nom : null; 
    }
    public function getEmailVerifiedAtAttribute()
    {
        // ⭐ Utiliser l'accesseur Laravel natif qui gère déjà le cast datetime
        $value = $this->attributes['email_verified_at'] ?? null;

        if (!$value) {
            return null;
        }

        return date('d-m-Y H:i', strtotime($value));
    }
    /**
     * ⭐ Surcharger la méthode pour utiliser notre notification personnalisée
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification());
    }
    
} 
