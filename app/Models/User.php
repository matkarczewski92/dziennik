<?php

namespace App\Models;

use App\Notifications\PolishResetPasswordNotification;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_blocked',
        'blocked_at',
        'blocked_reason',
        'last_seen_at',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_blocked' => 'boolean',
            'blocked_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }

    public function activitiesAsCauser(): HasMany
    {
        return $this->hasMany(UserActivity::class, 'causer_id');
    }

    public function activitiesAsActor(): HasMany
    {
        return $this->hasMany(UserActivity::class, 'acted_as_id');
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new PolishResetPasswordNotification($token));
    }
}
