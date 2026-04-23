<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        if ($app->environment('testing') && config('database.default') === 'mysql') {
            $database = (string) config('database.connections.mysql.database');
            if ($database !== 'amazpey_test') {
                throw new \RuntimeException(
                    'Tests refuse to run against MySQL database ['.$database.']. '.
                    'They must use `amazpey_test` only (see phpunit.xml <env DB_DATABASE>). '.
                    'Create it: CREATE DATABASE amazpey_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
                );
            }
        }

        return $app;
    }
}
