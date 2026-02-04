<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalGenotype extends Model
{
    use HasFactory;

    protected $table = 'animal_genotype';

    protected $fillable = [
        'genotype_id',
        'animal_id',
        'type',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function genotypeCategory(): BelongsTo
    {
        return $this->belongsTo(AnimalGenotypeCategory::class, 'genotype_id');
    }
}

