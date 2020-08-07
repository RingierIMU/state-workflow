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
            return new WorkflowRegistry(
                collect($this->app['config']->get('workflow'))
                    ->except('setup')
                    ->toArray()
            );
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
     * Return migrations path.
     *
     * @return string
     */
    private function migrationPath()
    {
        return __DIR__.'/../database/migrations';
    }

    /**
     * Publish config file.
     */
    protected function publishConfig()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->configPath() => config_path('workflow.php'),
            ], 'state-workflow-config');

            $this->publishes([
                $this->migrationPath() => database_path('migrations'),
            ], 'state-workflow-migrations');
        }
    }

    /**
     * Load migration files.
     */
    protected function loadMigrations()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom($this->migrationPath());
        }
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
