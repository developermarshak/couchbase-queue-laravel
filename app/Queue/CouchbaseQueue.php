<?php
namespace App\Queue;
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 15.11.17
 * Time: 11:45
 */

use Illuminate\Database\Query\Builder;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Queue\Jobs\DatabaseJobRecord;
use Mpociot\Couchbase\Connection;

class CouchbaseQueue extends DatabaseQueue
{
    /**
     * The database connection instance.
     *
     * @var Connection
     */
    protected $database;

    /**
     * The database table that holds the jobs.
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the default queue.
     *
     * @var string
     */
    protected $default;

    /**
     * The expiration time of a job.
     *
     * @var int|null
     */
    protected $retryAfter = 60;

    /**
     * Create a new database queue instance.
     *
     * @param  Connection  $database
     * @param  string  $table
     * @param  string  $default
     * @param  int  $retryAfter
     */
    public function __construct(Connection $database, $table, $default = 'default', $retryAfter = 60)
    {
        $this->table = $table;
        $this->default = $default;
        $this->database = $database;
        $this->retryAfter = $retryAfter;
    }
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        if ($job = $this->getNextAvailableJob($queue)) {
            return $this->marshalJob($queue, $job);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        foreach ((array)$jobs as $job) {
            $this->push($job, $data, $queue);
        }
    }
    /**
     * {@inheritdoc}
     */
    public function deleteReserved($queue, $id)
    {
        $this->database->table($this->table)->where('id', $id)->delete();
    }

    /**
     * {@inheritdoc}
     */
    protected function marshalJob($queue, $job)
    {
        $job = $this->markJobAsReserved($job);
        return new DatabaseJob(
            $this->container, $this, $job, $this->connectionName, $queue
        );
    }
    /**
     * {@inheritdoc}
     */
    protected function getNextAvailableJob($queue)
    {
        $job = $this->database->table($this->table)
            ->where('queue', $this->getQueue($queue))
            ->where(function (Builder $query) {
                $this->isAvailable($query);
                $this->isReservedButExpired($query);
            })
            ->orderBy('id', 'asc')
            ->first();

        return $job ? new DatabaseJobRecord((object)$job) : null;
    }
    /**
     * {@inheritdoc}
     */
    protected function markJobAsReserved($job)
    {
        // lock bucket
        $bucket = $this->database->getCouchbaseBucket();
        $meta = $bucket->getAndLock($job->_id, 10);
        $meta->value->attempts = $job->attempts + 1;
        $meta->value->reserved_at = $job->touch();
        $bucket->replace($job->_id, $meta->value, ['cas' => $meta->cas]);
        return $meta->value;
    }

    /**
     * {@inheritdoc}
     */
    protected function pushToDatabase($queue, $payload, $delay = 0, $attempts = 0)
    {
        $attributes = $this->buildDatabaseRecord(
            $this->getQueue($queue), $payload, $this->availableAt($delay), $attempts
        );
        $increment = $this->incrementKey();
        $attributes['id'] = $increment;
        $result = $this->database->table($this->table)->insert($attributes);
        if ($result) {
            return $increment;
        }
        return false;
    }
    /**
     * generate increment key
     *
     * @param int $initial
     *
     * @return int
     */
    protected function incrementKey($initial = 1)
    {
        $result = $this->database->getCouchbaseBucket()
            ->counter($this->identifier(), $initial, ['initial' => abs($initial)]);
        return $result->value;
    }
    /**
     * @param array $attributes
     *
     * @return string
     */
    protected function uniqueKey(array $attributes): string
    {
        $array = array_only($attributes, ['queue', 'attempts', 'id']);
        return implode(':', $array);
    }
    /**
     * @return string
     */
    protected function identifier(): string
    {
        return __CLASS__ . ':sequence';
    }
}