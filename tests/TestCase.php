<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear Spatie permission cache between tests so Role::create() doesn't
        // see stale cached roles after RefreshDatabase rolls back the transaction.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
