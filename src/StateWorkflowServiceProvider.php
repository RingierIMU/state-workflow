<?php

namespace Ringierimu\StateWorkflow;

use Illuminate\Support\ServiceProvider;
use Ringierimu\StateWorkflow\Console\Commands\StateWorkflowDumpCommand;
use Ringierimu\StateWorkflow\Interfaces\WorkflowRegistryInterface;

/**
 * Class StateWorkflowServiceProvider.
 */
class StateWorkflowServiceProvider extends ServiceProvider
{
    /**
     *  Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishConfig();
        $this->loadMigrations();
        $this->registerCommands();
    }

    /**
     *  Register the application services...
     */
    public function register()
    {
        $this->mergeConfigFrom($this->configPath(), 'workflow');

        $this->app->singleton('stateWorkflow', function () {
            return new WorkflowRegistry($this->app['config']->get('workflow'));
        });

        $this->app->alias('stateWorkflow', WorkflowRegistryInterface::class);
    }

    /**
     * Return config file.
     *
     * @return string
     */
    protected function configPath()
    {
        return __DIR__.'/../config/workflow.php';
    }

    /**
     * Publish config file.
     */
    protected function publishConfig()
    {
        $this->publishes([
            $this->configPath() => config_path('workflow.php'),
        ], 'config');
    }

    /**
     * Load migration files.
     */
    protected function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Register Artisan commands.
     */
    protected function registerCommands()
    {
        $this->commands([
            StateWorkflowDumpCommand::class,
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['stateWorkflow'];
    }
}
