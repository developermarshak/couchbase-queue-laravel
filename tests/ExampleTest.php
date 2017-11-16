<?php
namespace tests;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use \Illuminate\Support\Facades\Queue;

class ExampleTest extends TestCase
{
    const OUT_FILE = "/../storage/tmp_out_queue.file";

    public function testAtomicity()
    {
        $taskCount = 60;
        $this->addTasks($taskCount);


    }

    protected function addTasks($count){
        for($i = 0; $i < $count; $i++){
            $job = new TestJob(static::OUT_FILE);
            Queue::push($job);
        }
    }

    protected function startQueueMultiThread($thread){
        for($i = 0; $i < $thread; $i++){
            popen("php artisan queue:work", );
        }
    }
}

class TestJob implements ShouldQueue{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $outFile;

    function __construct($outFile)
    {
        $this->outFile = $outFile;
    }

    function handle(){
        file_put_contents($this->outFile, $this->_id."\n", FILE_APPEND);
    }
}