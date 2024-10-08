<?php

namespace Ringierimu\StateWorkflow\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Ringierimu\StateWorkflow\StateWorkflowServiceProvider;
use Ringierimu\StateWorkflow\Tests\Fixtures\Models\User;
use Ringierimu\StateWorkflow\Tests\Fixtures\Traits\ConfigTrait;

abstract class TestCase extends OrchestraTestCase
{
    use ConfigTrait;
    use RefreshDatabase;
    use WithFaker;

    /** @var User */
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::create($this->userFactory());

        auth()->login(User::create($this->userFactory()));
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [StateWorkflowServiceProvider::class];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('workflow', $this->getWorflowConfig());
        parent::getEnvironmentSetUp($app);
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/Fixtures/database/migrations/');
    }

    private function userFactory(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
            'remember_token' => Str::random(10),
            'user_state' => 'new',
        ];
    }
}
