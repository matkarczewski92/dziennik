<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feed extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'feeding_interval',
        'amount',
        'last_price',
    ];

    protected function casts(): array
    {
        return [
            'feeding_interval' => 'integer',
            'amount' => 'integer',
            'last_price' => 'decimal:2',
        ];
    }

    public function feedings(): HasMany
    {
        return $this->hasMany(Feeding::class);
    }
}

