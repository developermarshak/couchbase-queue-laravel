<?php
namespace App\Queue;
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 15.11.17
 * Time: 11:44
 */

use Illuminate\Queue\Connectors\DatabaseConnector;

class CouchbaseConnector extends DatabaseConnector
{
    public function connect(array $config)
    {
        return new CouchbaseQueue(
            $this->connections->connection($config['connection'] ?? null),
            $config['table'],
            $config['queue'],
            $config['retry_after'] ?? 60
        );
    }
}