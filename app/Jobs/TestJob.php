<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 15.11.17
 * Time: 15:29
 */

namespace App\Jobs;


class TestJob extends Job
{
    function handle(){
        file_put_contents(storage_path("logs")."test.log", date("Y-m-d H:i:s")."\n", FILE_APPEND);
    }
}