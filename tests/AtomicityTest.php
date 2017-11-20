<?php
namespace developermarshak\QueueCouchbase\tests;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use \Illuminate\Support\Facades\Queue;

class AtomicityTest extends TestCase
{
    const OUT_FILE = "tmp_out_queue.file";

    function setUp()
    {
        parent::setUp();
        file_put_contents(static::OUT_FILE, '');
    }

    public function testAtomicity()
    {
        $taskCount = 60;
        $this->addTasks($taskCount);

        $this->startQueueMultiThread($taskCount);

        $this->assertFileExists(static::OUT_FILE);
        $content = trim(file_get_contents(static::OUT_FILE));
        $completeJobIds = explode("\n",$content);
        $this->assertCount($taskCount, $completeJobIds);

        $this->assertEquals(array_unique($completeJobIds), $completeJobIds);

    }

    function tearDown()
    {
        if(file_exists(static::OUT_FILE)){
            unlink(static::OUT_FILE);
        }
        parent::tearDown();
    }

    protected function addTasks($count){
        for($i = 0; $i < $count; $i++){
            $job = new TestJob(static::OUT_FILE, $i);
            Queue::push($job);
        }
    }

    protected function startQueueMultiThread($thread){
        $handlers = [];
        for($i = 0; $i < $thread; $i++){
            $handlers[$i] = popen("php artisan queue:work --once", "r");
        }

        while(count($handlers)){
            foreach ($handlers as $i => $handler){
                fread($handler, 2048);
                if(feof($handler)){
                    pclose($handler);
                    unset($handlers[$i]);
                }
            }
        }
    }
}

class TestJob implements ShouldQueue{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $outFile;

    protected $id;
    function __construct($outFile, $id)
    {
        $this->outFile = $outFile;
        $this->id = $id;
    }

    function handle(){
        file_put_contents($this->outFile, $this->id."\n", FILE_APPEND);
    }
}