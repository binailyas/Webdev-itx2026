<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Cache statis (pengaturan & izin) tidak boleh bocor antar tes.
        \App\Support\Perm::flush();
        if (function_exists('settings_cache')) {
            try { settings_cache(true); } catch (\Throwable) {}
        }
    }
}