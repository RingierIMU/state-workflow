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
    public function boot(): void
    {
        $this->publishConfig();
        $this->publishDatabase();
        $this->registerCommands();
    }

    /**
     *  Register the application services...
     */
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), 'workflow');

        $this->app->singleton('stateWorkflow', fn() => new WorkflowRegistry(
            collect($this->app['config']->get('workflow'))
                ->except('setup')
                ->toArray()
        ));

        $this->app->alias('stateWorkflow', WorkflowRegistryInterface::class);
    }

    /**
     * Return config file.
     */
    protected function configPath(): string
    {
        return __DIR__ . '/../config/workflow.php';
    }

    /**
     * Return migrations path.
     */
    private function migrationPath(): string
    {
        return __DIR__ . '/../database/migrations';
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
        }
    }

    protected function publishDatabase()
    {
        if ($this->app->runningInConsole()) {
            $path = 'migrations/' . date('Y_m_d_His', time());
            $this->publishes([
                $this->migrationPath() . '/create_state_workflow_histories_table.php' => database_path($path . '_create_state_workflow_histories_table.php'),
            ], 'state-workflow-migration');
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
    #[\Override]
    public function provides()
    {
        return ['stateWorkflow'];
    }
}
