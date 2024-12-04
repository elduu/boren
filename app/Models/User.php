<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Auth\Passwords\CanResetPassword;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Middlewares\RoleMiddleware;


use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable,CanResetPassword,HasRoles; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
       'name', 'email', 'phone', 'password', 'status',
       
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
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    
    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at')->get();
    }
    public function scopeAdmins($query)
{
    return $query->where('role', 'admin');
}

    /**
     * Retrieve only read notifications for the user.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function readNotifications()
    {
        return $this->notifications()->whereNotNull('read_at')->get();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
