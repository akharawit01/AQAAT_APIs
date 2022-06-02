<?php

namespace App\Http\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class AQI extends Model
{
    protected $collection = 'aqicollections';
    protected $dates = ['timestamp'];
}
