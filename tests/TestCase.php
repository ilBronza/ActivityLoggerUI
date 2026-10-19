<?php

namespace IlBronza\ActivityLoggerUI\Tests;

use IlBronza\ActivityLoggerUI\ActivityLoggerUI;
use IlBronza\ActivityLoggerUI\ActivityLoggerUIServiceProvider;
use IlBronza\ActivityLoggerUI\Tests\Fixtures\Record;
use IlBronza\ActivityLoggerUI\Tests\Fixtures\Storage;
use IlBronza\ActivityLoggerUI\Tests\Fixtures\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Activitylog\Models\Activity;

// The shared ibtest runner supplies its own bootstrap; load runtime paths too.
require_once __DIR__ . '/bootstrap.php';

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [ActivitylogServiceProvider::class, ActivityLoggerUIServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app->useStoragePath(__DIR__ . '/.runtime/storage');
        $app->usePublicPath(__DIR__ . '/.runtime/public');
        $app['config']->set('view.compiled', __DIR__ . '/.runtime/views');
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('t', 32)));
        $app['config']->set('app.locale', 'it');
        $app['config']->set('app.fallback_locale', 'en');
        $app['config']->set('database.default', 'testing');
        foreach (['testing', 'logs'] as $connection) {
            $app['config']->set('database.connections.' . $connection, [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ]);
        }
        $app['config']->set('session.driver', 'array');
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('activitylog.enabled', false);
        $app['config']->set('activitylog.database_connection', 'logs');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Relation::morphMap(['record' => Record::class, 'storage' => Storage::class, 'user' => User::class], false);
        Schema::create('records', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('visible')->default(true);
            $table->unsignedInteger('parent_id')->nullable();
            $table->timestamps();
        });
        Schema::create('storages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('record_id');
            $table->string('stored_city')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('record_links', function (Blueprint $table) {
            $table->unsignedInteger('parent_record_id');
            $table->unsignedInteger('peer_record_id');
        });
        Schema::connection('logs')->create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->string('description');
            $table->string('event')->nullable();
            $table->nullableMorphs('subject');
            $table->nullableMorphs('causer');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
        });
        $this->actingAs(User::create(['name' => 'Davide']));
    }

    protected function record(array $attributes = []): Record
    {
        // Direct insert avoids running the CRUD trait's write-time callbacks.
        $id = DB::table('records')->insertGetId($attributes + ['name' => 'Progetto Aurora']);

        return Record::findOrFail($id);
    }

    protected function activity(Model $subject, array $properties, array $attributes = []): Activity
    {
        return Activity::create($attributes + [
            'subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey(),
            'event' => 'updated', 'description' => 'updated', 'properties' => $properties,
            'created_at' => '2026-10-16 10:30:00', 'updated_at' => '2026-10-16 10:30:00',
        ]);
    }

    protected function history(): ActivityLoggerUI
    {
        return app(ActivityLoggerUI::class);
    }

    protected function tearDown(): void
    {
        Relation::morphMap([], false);
        parent::tearDown();
    }
}
