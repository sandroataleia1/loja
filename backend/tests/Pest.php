<?php

declare(strict_types=1);

uses(Tests\TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Test Case Traits
|--------------------------------------------------------------------------
*/
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/
expect()->extend('toBeApiSuccess', function (): \Pest\Expectation {
    return $this->toHaveKey('success', true);
});

expect()->extend('toBeApiError', function (): \Pest\Expectation {
    return $this->toHaveKey('success', false);
});
