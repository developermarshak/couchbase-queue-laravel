<?php
namespace App\Console\Commands;
use App\Jobs\TestJob;
use Illuminate\Console\Command;
use \Illuminate\Support\Facades\Queue;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 15.11.17
 * Time: 14:09
 */

class AddTestJob extends Command
{
    protected $signature = "testJob:add";

    function handle(){
        for($i = 0; $i < 300; $i++){
            $job = new TestJob();
            Queue::push($job);
        }
    }
}