<?php

Route::group([
	'middleware' => ['web', 'auth'],
	'prefix' => 'activity-log-management',
	'namespace' => 'IlBronza\ActivityLoggerUI\Http\Controllers'
	],
	function()
	{
		Route::get('{classBasename}/{primary}/index', 'CrudActivityController@activityIndex')->name('activities.index');
	});
