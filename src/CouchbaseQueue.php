<?php
namespace developermarshak\QueueCouchbase;
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 15.11.17
 * Time: 11:45
 */

use Couchbase\Exception as CouchbaseException;
use Illuminate\Database\Query\Builder;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Queue\Jobs\DatabaseJobRecord;
use Illuminate\Support\Facades\Log;
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
    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        if(!is_null($queue)){
            return parent::push($job, $data, (string) $queue);
        }
        return parent::push($job, $data, $queue);
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
        if(!$job = $this->markJobAsReserved($job)){
            return null;
        }
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

        $bucket = $this->database->getCouchbaseBucket();
        try{
            $meta = $bucket->getAndLock($job->_id, 5);// lock job
            if($meta->value->attempts != $job->attempts){
                $bucket->unlock($job->_id, ['cas' => $meta->cas]); //unlock if job worked on another process
                return false; //miss job
            }

            $meta->value->attempts = $job->attempts + 1;
            $meta->value->reserved_at = $job->touch();
            $bucket->replace($job->_id, $meta->value, ['cas' => $meta->cas]);
        }
        catch (CouchbaseException $e){
            return false;
        }
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

/**
alias xphp='
php -dxdebug.remote_enable=1 \
-dxdebug.remote_host="10.0.10.160" \
-dxdebug.remote_handler=dbgp \
-dxdebug.remote_port=9000 \
-dxdebug.remote_autostart=1 \
-dxdebug.remote_log=/tmp/xdebug.log \
-dxdebug.remote_connect_back=0'

export PHP_IDE_CONFIG="serverName=queue"

xphp artisan queue:work
 */