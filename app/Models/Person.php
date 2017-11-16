<?php
namespace App\Models;
use Mpociot\Couchbase\Eloquent\Model;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 15.11.17
 * Time: 17:47
 */

class Person extends Model
{
    protected $table = "person";

    protected $fillable = ['name', 'age', 'rand'];
}