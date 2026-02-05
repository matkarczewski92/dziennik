<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\AnimalGenotype;
use App\Models\AnimalGenotypeCategory;
use App\Models\AnimalSpecies;
use App\Models\Feed;
use App\Models\Feeding;
use App\Models\Photo;
use App\Models\Shed;
use App\Models\User;
use App\Models\Weight;
use App\Exceptions\HodowlaApiException;
use App\Services\AnimalImportService;
use App\Services\HodowlaApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AnimalImportSchemaMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_maps_wrapped_api_schema_to_animal_and_related_records(): void
    {
        $user = User::factory()->create();

        $payload = [
            'data' => [
                'animal' => [
                    'id' => '123',
                    'name' => '<b>Mamba</b>',
                    'second_name' => '<i>Black</i>',
                    'sex' => '2',
                    'animal_type' => [
                        'id' => '77',
                        'name' => 'Python regius',
                    ],
                    'secret_tag' => 'QHPU4R',
                    'date_of_birth' => '2023-05-10',
                ],
                'genetics' => [
                    [
                        'id' => 1,
                        'type' => '2',
                        'category' => [
                            'id' => '10',
                            'name' => 'Pastel',
                            'gene_code' => 'PST',
                            'gene_type' => '1',
                        ],
                    ],
                ],
                'feedings' => [
                    [
                        'id' => 101,
                        'feed_id' => '3',
                        'feed_name' => 'Mouse',
                        'amount' => '1',
                        'created_at' => '2026-01-10T18:30:00+00:00',
                    ],
                ],
                'weights' => [
                    [
                        'id' => 201,
                        'value' => '123.4',
                        'created_at' => '2026-01-12T09:00:00+00:00',
                    ],
                ],
                'sheds' => [
                    [
                        'id' => 301,
                        'created_at' => '2026-01-15T07:45:00+00:00',
                    ],
                ],
                'gallery' => [
                    [
                        'id' => 501,
                        'url' => 'https://makssnake.pl/storage/animals/501.jpg',
                        'is_main' => 1,
                        'banner_position' => 0,
                        'website' => 1,
                    ],
                ],
                'litters' => [],
            ],
        ];

        $client = Mockery::mock(HodowlaApiClient::class);
        $client->shouldReceive('fetchAnimalBySecretTag')
            ->once()
            ->with('QHPU4R')
            ->andReturn($payload);

        $animal = (new AnimalImportService($client))->importBySecretTag($user, 'QHPU4R');

        $this->assertSame('Mamba', $animal->name);
        $this->assertSame('male', $animal->sex);
        $this->assertSame('2023-05-10', $animal->hatch_date?->toDateString());
        $this->assertSame('123', $animal->remote_id);
        $this->assertTrue($animal->imported_from_api);
        $this->assertSame('QHPU4R', $animal->secret_tag);
        $this->assertSame('123.40', number_format((float) $animal->current_weight_grams, 2, '.', ''));
        $this->assertSame('2026-01-10', $animal->last_fed_at?->toDateString());

        $this->assertDatabaseHas('animal_species', [
            'id' => 77,
            'name' => 'Python regius',
        ]);
        $this->assertSame(77, $animal->species_id);

        $this->assertDatabaseHas('animal_genotype_category', [
            'id' => 10,
            'name' => 'Pastel',
            'gene_code' => 'PST',
            'gene_type' => 'r',
        ]);
        $this->assertDatabaseHas('animal_genotype', [
            'animal_id' => $animal->id,
            'genotype_id' => 10,
            'type' => 'h',
        ]);

        $this->assertDatabaseHas('feeds', [
            'id' => 3,
            'name' => 'Mouse',
        ]);
        $this->assertDatabaseHas('feedings', [
            'animal_id' => $animal->id,
            'user_id' => $user->id,
            'feed_id' => 3,
            'prey' => 'Mouse',
            'quantity' => 1,
            'fed_at' => '2026-01-10',
        ]);

        $this->assertDatabaseHas('weights', [
            'animal_id' => $animal->id,
            'user_id' => $user->id,
            'weight_grams' => 123.4,
            'measured_at' => '2026-01-12',
        ]);

        $this->assertDatabaseHas('sheds', [
            'animal_id' => $animal->id,
            'user_id' => $user->id,
            'shed_at' => '2026-01-15',
        ]);

        $this->assertDatabaseHas('photos', [
            'animal_id' => $animal->id,
            'user_id' => $user->id,
            'path' => 'https://makssnake.pl/storage/animals/501.jpg',
            'mime_type' => 'image/jpeg',
        ]);
    }

    public function test_repeated_import_does_not_duplicate_related_records(): void
    {
        $user = User::factory()->create();
        $payload = [
            'data' => [
                'animal' => [
                    'id' => 123,
                    'name' => 'Mamba',
                    'sex' => 2,
                    'animal_type' => ['id' => 77, 'name' => 'Python regius'],
                    'secret_tag' => 'QHPU4R',
                ],
                'genetics' => [
                    [
                        'type' => 2,
                        'category' => ['id' => 10, 'name' => 'Pastel', 'gene_code' => 'PST', 'gene_type' => 1],
                    ],
                ],
                'feedings' => [
                    [
                        'feed_id' => 3,
                        'feed_name' => 'Mouse',
                        'amount' => 1,
                        'created_at' => '2026-01-10T18:30:00+00:00',
                    ],
                ],
                'weights' => [
                    ['value' => 123.4, 'created_at' => '2026-01-12T09:00:00+00:00'],
                ],
                'sheds' => [
                    ['created_at' => '2026-01-15T07:45:00+00:00'],
                ],
                'gallery' => [
                    ['id' => 501, 'url' => 'https://makssnake.pl/storage/animals/501.jpg', 'is_main' => 1, 'banner_position' => 0, 'website' => 1],
                ],
                'litters' => [],
            ],
        ];

        $client = Mockery::mock(HodowlaApiClient::class);
        $client->shouldReceive('fetchAnimalBySecretTag')->twice()->andReturn($payload);

        $service = new AnimalImportService($client);
        $service->importBySecretTag($user, 'QHPU4R');
        $service->importBySecretTag($user, 'QHPU4R');

        $animal = Animal::query()->where('user_id', $user->id)->where('secret_tag', 'QHPU4R')->firstOrFail();

        $this->assertSame(1, AnimalSpecies::query()->where('id', 77)->count());
        $this->assertSame(1, AnimalGenotypeCategory::query()->where('id', 10)->count());
        $this->assertSame(1, Feed::query()->where('id', 3)->count());
        $this->assertSame(1, AnimalGenotype::query()->where('animal_id', $animal->id)->count());
        $this->assertSame(1, Feeding::query()->where('animal_id', $animal->id)->count());
        $this->assertSame(1, Weight::query()->where('animal_id', $animal->id)->count());
        $this->assertSame(1, Shed::query()->where('animal_id', $animal->id)->count());
        $this->assertSame(1, Photo::query()->where('animal_id', $animal->id)->count());
    }

    public function test_import_accepts_payload_without_data_wrapper(): void
    {
        $user = User::factory()->create();

        $payload = [
            'animal' => [
                'id' => '123',
                'name' => '<b>Mamba</b>',
                'sex' => '2',
                'animal_type' => ['id' => '77', 'name' => 'Python regius'],
                'secret_tag' => 'QHPU4R',
            ],
            'genetics' => [],
            'feedings' => [],
            'weights' => [],
            'sheds' => [],
            'litters' => [],
            'gallery' => [],
        ];

        $client = Mockery::mock(HodowlaApiClient::class);
        $client->shouldReceive('fetchAnimalBySecretTag')->once()->andReturn($payload);

        $animal = (new AnimalImportService($client))->importBySecretTag($user, 'QHPU4R');

        $this->assertSame('Mamba', $animal->name);
        $this->assertSame('123', $animal->remote_id);
    }

    public function test_import_throws_when_required_arrays_are_missing(): void
    {
        $this->expectException(HodowlaApiException::class);
        $this->expectExceptionMessage('payload.feedings');

        $user = User::factory()->create();
        $payload = [
            'data' => [
                'animal' => [
                    'id' => 1,
                    'name' => 'Mamba',
                    'sex' => 2,
                    'secret_tag' => 'QHPU4R',
                ],
                'genetics' => [],
                // feedings brak celowo
                'weights' => [],
                'sheds' => [],
                'litters' => [],
                'gallery' => [],
            ],
        ];

        $client = Mockery::mock(HodowlaApiClient::class);
        $client->shouldReceive('fetchAnimalBySecretTag')->once()->andReturn($payload);

        (new AnimalImportService($client))->importBySecretTag($user, 'QHPU4R');
    }

    public function test_import_is_blocked_when_secret_tag_is_already_assigned_to_other_user(): void
    {
        $this->expectException(HodowlaApiException::class);
        $this->expectExceptionMessage('juz przypisane do innego konta');

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        Animal::query()->create([
            'user_id' => $owner->id,
            'name' => 'Istniejacy',
            'secret_tag' => 'QHPU4R',
            'feeding_interval_days' => 14,
        ]);

        $payload = [
            'data' => [
                'animal' => [
                    'id' => 123,
                    'name' => 'Mamba',
                    'sex' => 2,
                    'animal_type' => ['id' => 77, 'name' => 'Python regius'],
                    'secret_tag' => 'QHPU4R',
                ],
                'genetics' => [],
                'feedings' => [],
                'weights' => [],
                'sheds' => [],
                'litters' => [],
                'gallery' => [],
            ],
        ];

        $client = Mockery::mock(HodowlaApiClient::class);
        $client->shouldReceive('fetchAnimalBySecretTag')->once()->andReturn($payload);

        (new AnimalImportService($client))->importBySecretTag($otherUser, 'QHPU4R');
    }
}
