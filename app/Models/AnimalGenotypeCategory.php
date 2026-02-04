<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnimalGenotypeCategory extends Model
{
    use HasFactory;

    protected $table = 'animal_genotype_category';

    protected $fillable = [
        'name',
        'gene_code',
        'gene_type',
    ];

    public function animalGenotypes(): HasMany
    {
        return $this->hasMany(AnimalGenotype::class, 'genotype_id');
    }

    public function animals(): BelongsToMany
    {
        return $this->belongsToMany(
            Animal::class,
            'animal_genotype',
            'genotype_id',
            'animal_id',
        )->withPivot('id', 'type', 'created_at', 'updated_at');
    }
}

