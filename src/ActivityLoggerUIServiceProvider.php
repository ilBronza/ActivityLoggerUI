<?php

namespace IlBronza\ActivityLoggerUI;

use Illuminate\Support\ServiceProvider;

class ActivityLoggerUIServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'activityloggerui');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'activityloggerui');
        $this->loadRoutesFrom(__DIR__ . '/../routes/activityloggerui.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/activityloggerui.php' => config_path('activityloggerui.php'),
            ], 'activityloggerui.config');
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/activityloggerui'),
            ], 'activityloggerui.views');
            $this->publishes([
                __DIR__ . '/../resources/lang' => lang_path('vendor/activityloggerui'),
            ], 'activityloggerui.lang');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/activityloggerui.php', 'activityloggerui');
        $this->app->singleton(ActivityLoggerUI::class);
        $this->app->alias(ActivityLoggerUI::class, 'activityloggerui');
    }
}
