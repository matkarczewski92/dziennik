<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! app()->environment('testing')) {
            return;
        }

        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($database === 'm2531_dziennik') {
            throw new RuntimeException(
                'Safety stop: tests cannot run on m2531_dziennik. Use dedicated test DB (m2531_dziennik_test).'
            );
        }
    }
}
