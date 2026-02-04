<?php

namespace App\Livewire\Animals;

use Livewire\Component;

class AnimalSidebarDetails extends Component
{
    public array $identity = [];

    public function render()
    {
        return view('livewire.animals.animal-sidebar-details');
    }
}
