<div class="container-fluid px-0 animal-life-page">
    <livewire:animals.animal-hero
        :animal-id="$animal->id"
        :identity="$profile->identity"
        :genotype-chips="$profile->genotypeChips"
        :wire:key="'animal-hero-'.$animal->id.'-'.count($profile->genotypeChips).'-'.md5((string) ($profile->identity['cover_photo_url'] ?? '')).'-'.optional($animal->updated_at)->timestamp"
    />
    <livewire:animals.animal-gallery-panel :animal-id="$animal->id" :wire:key="'animal-gallery-'.$animal->id" />

    <div class="row g-3 align-items-start mx-0">
        <div class="col-12 col-xl-3">
            <livewire:animals.animal-sidebar-details
                :animal-id="$animal->id"
                :identity="$profile->identity"
                :wire:key="'animal-sidebar-'.$animal->id.'-'.($profile->identity['id'] ?? $animal->id)"
            />
            <livewire:animals.animal-genetics-chips :animal-id="$animal->id" :wire:key="'animal-genetics-'.$animal->id" />
        </div>

        <div class="col-12 col-xl-6">
            <livewire:animals.animal-feedings-panel :animal-id="$animal->id" :wire:key="'animal-feedings-'.$animal->id" />
            <livewire:animals.animal-weights-chart :animal-id="$animal->id" :wire:key="'animal-weights-'.$animal->id" />
        </div>

        <div class="col-12 col-xl-3">
            <livewire:animals.animal-sheds-widget :animal-id="$animal->id" :wire:key="'animal-sheds-'.$animal->id" />
        </div>
    </div>

</div>
