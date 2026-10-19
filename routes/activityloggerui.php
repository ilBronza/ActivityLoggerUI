<?php

use IlBronza\ActivityLoggerUI\Http\Controllers\CrudActivityController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => config('activityloggerui.middleware', ['web', 'auth']),
    'prefix' => config('activityloggerui.route_prefix', 'activity-log-management'),
], function () {
    Route::get('{model}/{id}', [CrudActivityController::class, 'history'])
        ->name('activityloggerui.history');

    // Keep existing links using classBasename/primary working.
    Route::get('{classBasename}/{primary}/index', [CrudActivityController::class, 'activityIndex'])
        ->name('activities.index');
});
