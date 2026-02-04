<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Animal extends Model
{
    /** @use HasFactory<\Database\Factories\AnimalFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'species_id',
        'morph',
        'sex',
        'hatch_date',
        'acquired_at',
        'current_weight_grams',
        'feeding_interval_days',
        'last_fed_at',
        'secret_tag',
        'remote_id',
        'imported_from_api',
        'api_snapshot',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'hatch_date' => 'date',
            'acquired_at' => 'date',
            'last_fed_at' => 'date',
            'current_weight_grams' => 'decimal:2',
            'species_id' => 'integer',
            'imported_from_api' => 'boolean',
            'api_snapshot' => 'array',
        ];
    }

    public function scopeOwnedBy(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where('user_id', $userId);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(AnimalSpecies::class, 'species_id');
    }

    public function feedings(): HasMany
    {
        return $this->hasMany(Feeding::class)->latest('fed_at');
    }

    public function weights(): HasMany
    {
        return $this->hasMany(Weight::class)->latest('measured_at');
    }

    public function sheds(): HasMany
    {
        return $this->hasMany(Shed::class)->latest('shed_at');
    }

    public function notesRecords(): HasMany
    {
        return $this->hasMany(Note::class)->latest();
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class)->latest();
    }

    public function animalGenotypes(): HasMany
    {
        return $this->hasMany(AnimalGenotype::class, 'animal_id');
    }

    public function genotypeCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            AnimalGenotypeCategory::class,
            'animal_genotype',
            'animal_id',
            'genotype_id',
        )->withPivot('id', 'type', 'created_at', 'updated_at');
    }

    public function animalGenotypeCategories(): BelongsToMany
    {
        return $this->genotypeCategories();
    }
}
