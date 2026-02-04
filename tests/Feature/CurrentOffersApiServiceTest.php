<?php

namespace Tests\Feature;

use App\Exceptions\HodowlaApiException;
use App\Services\CurrentOffersApiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurrentOffersApiServiceTest extends TestCase
{
    public function test_it_maps_current_offers_from_wrapped_response(): void
    {
        config()->set('hodowla.base_url', 'https://makssnake.pl/api');

        Http::fake([
            'https://makssnake.pl/api/offers/current' => Http::response([
                'data' => [
                    [
                        'offer_id' => 11,
                        'animal_id' => 1,
                        'name' => '<b>Ultramel</b>',
                        'sex' => 2,
                        'sex_label' => 'samiec',
                        'price' => 2500,
                        'has_reservation' => true,
                        'date_of_birth' => '2022-08-30',
                        'main_photo_url' => 'https://cdn.example.com/a.jpg',
                        'public_profile_url' => 'https://www.makssnake.pl/profile/QHPU4R',
                        'type_id' => 1,
                        'type_name' => 'Waz zbozowy',
                    ],
                ],
            ], 200),
        ]);

        $offers = app(CurrentOffersApiService::class)->fetchCurrentOffers();

        $this->assertCount(1, $offers);
        $this->assertSame('Ultramel', $offers[0]['name']);
        $this->assertSame('Waz zbozowy', $offers[0]['type_name']);
        $this->assertTrue($offers[0]['has_reservation']);
    }

    public function test_it_falls_back_when_type_fields_are_missing(): void
    {
        config()->set('hodowla.base_url', 'https://makssnake.pl/api');

        Http::fake([
            'https://makssnake.pl/api/offers/current' => Http::response([
                'data' => [
                    [
                        'offer_id' => 11,
                        'name' => 'Ultramel',
                        'sex' => 2,
                        'price' => 2500,
                        'type' => [
                            'id' => 1,
                            'name' => 'Waz zbozowy',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $offers = app(CurrentOffersApiService::class)->fetchCurrentOffers();

        $this->assertSame(1, $offers[0]['type_id']);
        $this->assertSame('Waz zbozowy', $offers[0]['type_name']);
    }

    public function test_it_throws_readable_exception_when_api_fails(): void
    {
        config()->set('hodowla.base_url', 'https://makssnake.pl/api');

        Http::fake([
            'https://makssnake.pl/api/offers/current' => Http::response([], 500),
        ]);

        $this->expectException(HodowlaApiException::class);
        $this->expectExceptionMessage('Nie udalo sie pobrac aktualnej oferty');

        app(CurrentOffersApiService::class)->fetchCurrentOffers();
    }

    public function test_it_decodes_bom_wrapped_json_response(): void
    {
        config()->set('hodowla.base_url', 'https://makssnake.pl/api');

        $json = json_encode([
            'data' => [[
                'offer_id' => 183,
                'animal_id' => 135,
                'type_id' => 1,
                'type_name' => 'Waz zbozowy',
                'name' => 'Sample',
                'sex' => 3,
                'sex_label' => 'samica',
                'price' => 450,
                'has_reservation' => false,
                'date_of_birth' => '2025-07-03',
                'main_photo_url' => 'https://cdn.example.com/photo.jpg',
                'public_profile_url' => 'https://www.makssnake.pl/profile/SAMPLE',
            ]],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        Http::fake([
            'https://makssnake.pl/api/offers/current' => Http::response("\xEF\xBB\xBF".$json, 200, [
                'Content-Type' => 'application/json; charset=UTF-8',
            ]),
        ]);

        $offers = app(CurrentOffersApiService::class)->fetchCurrentOffers();

        $this->assertSame(183, $offers[0]['offer_id']);
        $this->assertSame('samica', $offers[0]['sex_label']);
    }
}
