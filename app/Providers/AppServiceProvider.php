<?php

namespace App\Providers;

use App\Models\AnimalGenotype;
use App\Models\AnimalOffer;
use App\Models\Feeding;
use App\Models\Photo;
use App\Models\Shed;
use App\Models\Weight;
use App\Observers\AnimalGenotypeObserver;
use App\Observers\AnimalOfferObserver;
use App\Observers\FeedingObserver;
use App\Observers\PhotoObserver;
use App\Observers\ShedObserver;
use App\Observers\WeightObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('components.pagination.custom');
        Paginator::defaultSimpleView('components.pagination.simple-custom');

        Feeding::observe(FeedingObserver::class);
        Weight::observe(WeightObserver::class);
        Shed::observe(ShedObserver::class);
        AnimalGenotype::observe(AnimalGenotypeObserver::class);
        Photo::observe(PhotoObserver::class);
        AnimalOffer::observe(AnimalOfferObserver::class);
    }
}
