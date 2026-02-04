<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalEvent extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'animal_id',
        'type',
        'happened_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'animal_id' => 'integer',
            'happened_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
