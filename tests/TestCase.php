<?php
namespace developermarshak\QueueCouchbase\tests;

abstract class TestCase extends \Laravel\Lumen\Testing\TestCase
{
    protected $connection = null;

    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__ . '/app/app.php';
    }

    function setUp()
    {
        parent::setUp();
        $this->app->withFacades();
    }

    /**
     * Get a database connection instance.
     *
     * @return \Mpociot\Couchbase\Connection
     */
    protected function connection()
    {
        if(is_null($this->connection)){
            $this->connection = app('db')->connection('couchbase');
        }
        return $this->connection;
    }

    function tearDown()
    {
        $this->connection()->table('jobs')->delete();
        parent::tearDown();
    }
}
