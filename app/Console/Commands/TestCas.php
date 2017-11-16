<?php
namespace App\Console\Commands;
use App\Jobs\TestJob;
use App\Models\Person;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use \Illuminate\Support\Facades\Queue;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 15.11.17
 * Time: 14:09
 */

class TestCas extends Command
{
    protected $signature = "testCas:start";

    function handle(){
        $r = time();
        $model = new Person([
            'rand' => $r,
            'name' => "Genry",
            'age'  => 99
        ]);

        $model->save();

        $connection = DB::connection('couchbase');
        /**
         * @var \Mpociot\Couchbase\Connection $connection
         */

        $bucket = $connection->getCouchbaseBucket();

        $data = $bucket->getAndLock($model->_id, 20);

        $model->age += 1;
        var_dump($bucket->replace($model->_id, $model->attributesToArray(), ["cas" => $data->cas]));

        $data = $bucket->getAndLock($model->_id, 20);
        $model->age += 1;
        var_dump($bucket->replace($model->_id, $model, $data->cas));
    }
}

/*
alias xphp='
php -dxdebug.remote_enable=1 \
-dxdebug.remote_host="172.17.0.1" \
-dxdebug.remote_handler=dbgp \
-dxdebug.remote_port=9000 \
-dxdebug.remote_autostart=1 \
-dxdebug.remote_log=/tmp/xdebug.log \
-dxdebug.remote_connect_back=0'

export PHP_IDE_CONFIG="serverName=queue"
 */