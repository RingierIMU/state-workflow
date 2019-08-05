<?php

namespace Ringierimu\StateWorkflow\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Ringierimu\StateWorkflow\Workflow\StateWorkflow;
use Symfony\Component\Process\Process;
use Symfony\Component\Workflow\Dumper\GraphvizDumper;

/**
 * Class StateWorkflowDumpCommand.
 *
 * Symfony dump workflow https://symfony.com/doc/current/workflow/dumping-workflows.html
 *
 *
 * @author Norby Baruani <norbyb@roam.africa/>
 */
class StateWorkflowDumpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workflow:dump
                            {workflow : name of workflow from configuration}
                            {--format=png : the image format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dumps a State Workflow as a graphviz file using GraphvizDumper.';

    /**
     * @throws \ReflectionException
     * @throws Exception
     */
    public function handle()
    {
        $workflowName = $this->argument('workflow');
        $format = $this->option('format');
        $config = Config::get('workflow');

        if (!isset($config[$workflowName])) {
            throw new Exception("Workflow $workflowName is not configured. Make sure it is configured correctly on the config file.");
        }

        if (!$config[$workflowName]['class']) {
            throw new Exception("Workflow $workflowName has no class");
        }

        $class = $config[$workflowName]['class'];

        $ref = new \ReflectionClass($class);
        $model = $ref->newInstance();

        if (!method_exists($model, 'workflow')) {
            throw new Exception("Class $class does not support State Workflow. Make sure Class is configured correctly");
        }

        /** @var StateWorkflow $workflow */
        $workflow = $model->workflow();
        $definition = $workflow->getDefinition();

        $dumper = new GraphvizDumper();

        $dotCommand = "dot -T$format -o $workflowName.$format";

        $process = new Process($dotCommand);
        $process->setInput($dumper->dump($definition));
        $process->mustRun();
    }
}
