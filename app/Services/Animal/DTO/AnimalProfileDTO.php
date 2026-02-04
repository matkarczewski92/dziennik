<?php

namespace App\Services\Animal\DTO;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AnimalProfileDTO
{
    public function __construct(
        public readonly array $identity,
        public readonly array $genotypeChips,
        public readonly array $feedingsByYear,
        public readonly LengthAwarePaginator $sheds,
        public readonly ?array $offerStatus,
        public readonly array $weightChartDataset,
        public readonly array $timelineByMonth,
    ) {
    }
}
