<?php
/**
* Created by PhpStorm.
 * User: admin
 * Date: 15.11.17
 * Time: 11:42
 */

namespace App\Providers;

use App\Queue\CouchbaseConnector;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;

class CouchbaseQueueServiceProvider extends ServiceProvider
{
    public function register(){
        $queueManager = $this->app->make("queue");
        $this->addCouchbaseServiceProvider($queueManager);
    }

    protected function addCouchbaseServiceProvider(QueueManager $manager){
        $manager->addConnector('couchbase', function () {
            return new CouchbaseConnector($this->app['db']);
        });
    }
}