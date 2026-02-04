<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feeding extends Model
{
    /** @use HasFactory<\Database\Factories\FeedingFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'animal_id',
        'feed_id',
        'fed_at',
        'prey',
        'prey_weight_grams',
        'quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'fed_at' => 'date',
            'feed_id' => 'integer',
            'prey_weight_grams' => 'decimal:2',
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

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
