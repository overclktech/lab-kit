<?php

use Illuminate\Support\Facades\Route;

it('registers no http route', function (): void {
    expect(Route::getRoutes()->getRoutes())->toBe([]);
});
