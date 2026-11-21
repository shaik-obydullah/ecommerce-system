<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Catalog, FX, and settings values are cached in Redis. Tests share that
        // Redis instance, so clear it before each test to keep cases independent.
        Cache::flush();
    }
}
