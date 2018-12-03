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
        $configPath = $this->configPath();

        $this->publishes([
            $configPath => config_path('workflow.php')
        ], 'config');
    }

    /**
     *  Register the application services...
     */
    public function register()
    {
       // $this->mergeConfigFrom($this->configPath(), 'workflow');

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
        return __DIR__ . '/config/workflow.php';
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
