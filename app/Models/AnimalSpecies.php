<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnimalSpecies extends Model
{
    use HasFactory;

    protected $table = 'animal_species';

    protected $fillable = [
        'name',
    ];

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class, 'species_id');
    }
}

