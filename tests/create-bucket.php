<?php

require __DIR__ . '/../vendor/autoload.php';

try {
    $envFile = env('APP_ENV', 'local') !== 'testing' ? '.env' : '.env.testing';

    if (!file_exists($envFile) && file_exists('.env')) {
        $envFile = '.env';
    }

    (new Dotenv\Dotenv(__DIR__ . '/../', $envFile))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

class CreateBucketHelper
{
    const LIMIT_SLEEP_TIME = 60;
    protected $bucketName;
    protected $config;
    /**
     * @var Couchbase\Cluster
     */
    protected $cluster;

    function __construct()
    {
        $globalConfig = require __DIR__ . "/app/config/database.php";

        $this->config = $globalConfig['connections']['couchbase'];
        $this->bucketName = $this->config['bucket'];
    }

    function init()
    {
        $this->cluster = $this->connection();
        $this->createBucket();
        $this->createPrimaryIndex();
    }

    function reset()
    {
        $this->cluster = $this->connection();
        $this->removeBucket();
    }

    protected function connection()
    {
        $cluster = new Couchbase\Cluster("couchbase://" . $this->config["host"] . ":" . $this->config["port"]);

        $auth = new CouchbaseAuthenticator();
        $auth->cluster($this->config["user"], $this->config["password"]);

        $cluster->authenticate($auth);

        return $cluster;
    }

    protected function createBucket()
    {
        $manager = $this->cluster->manager($this->config["user"], $this->config["password"]);
        $manager->createBucket($this->bucketName);

        $sleepTime = 0;
        //Wait while set up bucket
        while (true) {
            sleep(1);

            $sleepTime++;

            echo "Wait bucket: " . $sleepTime . "\n";

            if ($sleepTime > static::LIMIT_SLEEP_TIME) {
                throw new Exception("Not set up bucket after: " . $sleepTime . " seconds");
            }

            $bucketInfo = $manager->listBuckets();

            if (is_array($bucketInfo) && count($bucketInfo)) {
                $bucketInfo = $bucketInfo[0];

                if (isset($bucketInfo['nodes'])) {

                    foreach ($bucketInfo['nodes'] as $nodeInfo) {
                        if ($nodeInfo['status'] != "healthy") {
                            continue 2;
                        }
                    }
                }
            }
            sleep(1);
            return;
        }
    }

    protected function removeBucket()
    {
        $manager = $this->cluster->manager($this->config["user"], $this->config["password"]);
        $manager->removeBucket($this->bucketName);
    }

    protected function createPrimaryIndex()
    {
        $bucket = $this->cluster->openBucket($this->bucketName);

        $waitTime = 0;
        while (true) {
            try {
                $bucket->manager()->createN1qlPrimaryIndex($this->bucketName . "-primary-index");
                break;
            } catch (Exception $e) {

                if (substr_count($e->getMessage(), ' already exists ') > 0) {
                    exit(0);
                }
                $waitTime++;
                sleep(1);
                echo "Catch: " . $waitTime . "\n";

                if ($waitTime > static::LIMIT_SLEEP_TIME) {
                    throw new Exception("Now work create primary index, wait time: " . $waitTime . " seconds");
                }

            }
        }

    }
}

$helper = new CreateBucketHelper();
$helper->init();
