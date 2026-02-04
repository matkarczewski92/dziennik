<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'price',
        'sold_date',
    ];

    protected function casts(): array
    {
        return [
            'animal_id' => 'integer',
            'price' => 'decimal:2',
            'sold_date' => 'date',
        ];
    }

    public function scopeOwnedBy(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->whereHas('animal', static function (Builder $animalQuery) use ($userId): void {
            $animalQuery->where('user_id', $userId);
        });
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function getStatusAttribute(): string
    {
        return $this->sold_date ? 'sold' : 'available';
    }
}

