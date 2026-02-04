<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shed extends Model
{
    /** @use HasFactory<\Database\Factories\ShedFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'animal_id',
        'shed_at',
        'quality',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'shed_at' => 'date',
        ];
    }

    public function scopeOwnedBy(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where('user_id', $userId);
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
