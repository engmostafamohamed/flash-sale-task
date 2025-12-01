<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Pest Test Configuration
|--------------------------------------------------------------------------
|
| This file sets up Pest for Laravel.
| You can register test hooks, use shared traits, etc.
|
*/

uses(TestCase::class, RefreshDatabase::class)->in('Feature', 'Unit');
