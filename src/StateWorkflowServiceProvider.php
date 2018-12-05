<?php namespace Oneafricamedia\StateWorkflow;

use Illuminate\Support\ServiceProvider;
use Oneafricamedia\StateWorkflow\Interfaces\WorkflowRegistryInterface;

/**
 * Class StateWorkflowServiceProvider
 * @package Oneafricamedia\StateWorkflow
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
     * Return config file
     *
     * @return string
     */
    protected function configPath()
    {
        return __DIR__ . '/../config/workflow.php';
    }

    /**
     * Publish config file
     */
    protected function publishConfig()
    {
        $this->publishes([
            $this->configPath() => config_path('workflow.php')
        ], 'config');
    }

    /**
     * Load migration files
     */
    protected function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
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
