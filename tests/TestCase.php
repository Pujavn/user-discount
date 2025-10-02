<?php
// tests/TestCase.php
namespace PujaNaik\UserDiscount\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use PujaNaik\UserDiscount\UserDiscountServiceProvider;
use Illuminate\Database\Schema\Blueprint;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [UserDiscountServiceProvider::class];
    }

    /** point the app at an in-memory sqlite DB */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.debug', true);
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
            'foreign_key_constraints' => true,
        ]);
    }

//     protected function getEnvironmentSetUp($app): void
//     {
//         $driver = env('TEST_DB_DRIVER', 'mysql');
//         echo $driver;
// if ($driver === 'mysql') {
//     $app['config']->set('database.default', 'testing');
//     $app['config']->set('database.connections.testing', [
//         'driver'    => 'mysql',
//         'host'      => env('TEST_DB_HOST', '127.0.0.1'),
//         'port'      => env('TEST_DB_PORT', '3306'),
//         'database'  => env('TEST_DB_DATABASE', 'user_discount_test'),
//         'username'  => env('TEST_DB_USERNAME', 'root'),
//         'password'  => env('TEST_DB_PASSWORD', ''),
//         'charset'   => 'utf8mb4',
//         'collation' => 'utf8mb4_unicode_ci',
//         'prefix'    => '',
//         'strict'    => false,
//     ]);
// }
    // }
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        // Minimal users table for tests
        $schema = $this->app['db']->connection('testing')->getSchemaBuilder();
        if (! $schema->hasTable('users')) {
            $schema->create('users', function (Blueprint $t) {
                // $t->engine = 'InnoDB';
                $t->id();
                $t->string('name')->nullable();
                $t->string('email')->nullable()->unique();
                $t->timestamps();
            });
        }

        // Load your package migrations (make sure these files exist)
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
