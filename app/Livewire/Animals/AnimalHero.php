<?php

namespace App\Livewire\Animals;

use Livewire\Component;

class AnimalHero extends Component
{
    public array $identity = [];

    public array $genotypeChips = [];

    public function render()
    {
        return view('livewire.animals.animal-hero');
    }
}
