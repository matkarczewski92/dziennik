<?php

namespace Tests\Feature;

use App\Models\AnimalGenotypeCategory;
use App\Models\AnimalSpecies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneticsDictionaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_species_and_genotype_categories_are_seeded_from_legacy_dump(): void
    {
        $this->seed();

        $this->assertSame(4, AnimalSpecies::count());
        $this->assertSame(25, AnimalGenotypeCategory::count());
        $this->assertDatabaseHas('animal_genotype_category', [
            'gene_code' => 'TT',
            'name' => 'Tessera',
        ]);
    }
}

