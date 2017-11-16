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
        var_dump($model->_id);
        $person = Person::where("rand", "=", $r)->first();

        $person->age = 13000;
        $person->save();
        var_dump(Person::where("rand", "=", $r)->first()->age);
    }
}